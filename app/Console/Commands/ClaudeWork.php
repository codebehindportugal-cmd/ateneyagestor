<?php

namespace App\Console\Commands;

use App\Support\ClaudeTaskPrompt;
use App\Support\ClaudeWorkspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Worker: vai buscar ao painel os pedidos feitos no botao "Resolver com o
 * Claude" e corre o Claude Code na pasta do projecto.
 *
 * Corre na maquina onde o codigo esta: o PC, um LXC no Proxmox, ou a propria
 * VPS. O painel esta em producao e nao chama ninguem — por isso a conversa e
 * sempre neste sentido, por HTTP, como nos sincronizadores.
 *
 *   php artisan claude:work            # fica a correr (ou claude-worker.bat)
 *   php artisan claude:work --once     # apanha um pedido e sai, para cron
 */
class ClaudeWork extends Command
{
    protected $signature = 'claude:work
        {--once : Apanha um pedido (ou nenhum) e sai}';

    protected $description = 'Corre no PC os pedidos ao Claude feitos nas tarefas do painel';

    public function handle(): int
    {
        if (blank(config('claude.panel.url')) || blank(config('claude.panel.token'))) {
            $this->error('Falta CLAUDE_PANEL_URL ou CLAUDE_PANEL_TOKEN no .env desta máquina.');
            $this->line('O token gera-se no painel, em Projectos > Token do worker.');

            return 1;
        }

        $this->info('À espera de pedidos de ' . config('claude.panel.url') . ' …');

        do {
            $trabalho = $this->fetchNext();

            if ($trabalho === null) {
                if ($this->option('once')) {
                    $this->line('Nada na fila.');

                    return 0;
                }

                sleep(max(1, (int) config('claude.sleep')));
                continue;
            }

            $this->process($trabalho);
        } while (! $this->option('once'));

        return 0;
    }

    /** @return array<string,mixed>|null */
    private function fetchNext(): ?array
    {
        try {
            $resposta = $this->panel()->get('/api/claude/next');
        } catch (\Throwable $e) {
            $this->warn('Não consegui falar com o painel: ' . $e->getMessage());
            sleep(max(5, (int) config('claude.sleep')));

            return null;
        }

        if ($resposta->status() === 401 || $resposta->status() === 403) {
            $this->error('O painel recusou o token. Gera outro em Projectos > Token do worker.');
            sleep(30);

            return null;
        }

        if (! $resposta->successful()) {
            $this->warn('O painel respondeu ' . $resposta->status() . '.');
            sleep(max(5, (int) config('claude.sleep')));

            return null;
        }

        $dados = $resposta->json();

        return ($dados['run'] ?? null) === null ? null : $dados;
    }

    /** @param array<string,mixed> $trabalho */
    private function process(array $trabalho): void
    {
        $runId = (int) $trabalho['run']['id'];
        $nome  = $trabalho['project']['name'] ?? '?';
        $titulo = $trabalho['task']['title'] ?? '?';

        // Preparar a pasta pode demorar: no caso remoto puxa a copia do servidor.
        try {
            $workspace = ClaudeWorkspace::fromDescriptor($trabalho['code'] ?? []);
        } catch (\Throwable $e) {
            $this->report($runId, ['status' => 'failed', 'error' => $e->getMessage()]);
            $this->error("  falhou: {$e->getMessage()}");

            return;
        }

        $prompt = ClaudeTaskPrompt::compose(
            $workspace->summary,
            (string) $trabalho['prompt_body'],
            $workspace->kind === 'none',
        );

        $this->info("[#{$runId}] {$nome} — {$titulo} ({$workspace->kind})");

        $process = new Process(
            $this->buildCommand($prompt, $trabalho['run']['previous_session_id'] ?? null),
            $workspace->path,
            $this->environment(),
        );
        $process->setTimeout((float) config('claude.timeout'));

        $inicio = microtime(true);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            $this->report($runId, ['status' => 'failed', 'prompt' => $prompt,
                'error' => 'O Claude passou do tempo limite de ' . config('claude.timeout') . ' s.']);

            return;
        } catch (\Throwable $e) {
            $this->report($runId, ['status' => 'failed', 'prompt' => $prompt,
                'error' => 'Não consegui arrancar o Claude: ' . $e->getMessage() . ' (confirma o CLAUDE_BINARY no .env)']);

            return;
        }

        $duracao = (int) round((microtime(true) - $inicio) * 1000);
        $saida   = trim($process->getOutput());

        if (! $process->isSuccessful() && $saida === '') {
            $this->report($runId, ['status' => 'failed', 'prompt' => $prompt, 'duration_ms' => $duracao,
                'error' => trim($process->getErrorOutput()) ?: 'O Claude saiu com código ' . $process->getExitCode() . '.']);

            return;
        }

        $json = json_decode($saida, true);

        if (! is_array($json) || ! array_key_exists('result', $json)) {
            // Sem JSON valido guarda-se a saida crua: e melhor do que perder a resposta.
            $this->report($runId, ['status' => 'failed', 'prompt' => $prompt, 'duration_ms' => $duracao,
                'error' => "Resposta inesperada do Claude:\n" . mb_substr($saida, 0, 2000)]);

            return;
        }

        if (($json['is_error'] ?? false) === true) {
            $this->report($runId, ['status' => 'failed', 'prompt' => $prompt, 'duration_ms' => $duracao,
                'error' => (string) $json['result']]);

            return;
        }

        $this->report($runId, [
            'status'      => 'done',
            'prompt'      => $prompt,
            'result'      => (string) $json['result'],
            'session_id'  => $json['session_id'] ?? null,
            'cost_usd'    => $json['total_cost_usd'] ?? null,
            'duration_ms' => $json['duration_ms'] ?? $duracao,
        ]);

        $this->info('  respondido' . (isset($json['total_cost_usd']) ? ' · $' . number_format((float) $json['total_cost_usd'], 3) : ''));
    }

    /** @return list<string> */
    private function buildCommand(string $prompt, ?string $sessaoAnterior): array
    {
        $comando = [
            (string) config('claude.binary'),
            '-p', $prompt,
            '--output-format', 'json',
            '--permission-mode', (string) config('claude.permission_mode'),
            '--allowedTools', (string) config('claude.allowed_tools'),
        ];

        if ($negados = config('claude.disallowed_tools')) {
            array_push($comando, '--disallowedTools', (string) $negados);
        }

        if (config('claude.bare')) {
            $comando[] = '--bare';
        }

        if ($modelo = config('claude.model')) {
            array_push($comando, '--model', (string) $modelo);
        }

        if ($sessaoAnterior) {
            array_push($comando, '--resume', $sessaoAnterior);
        }

        return $comando;
    }

    /** @param array<string,mixed> $payload */
    private function report(int $runId, array $payload): void
    {
        try {
            $resposta = $this->panel()->post("/api/claude/runs/{$runId}/finish", $payload);

            if (! $resposta->successful()) {
                $this->error("  o painel recusou o resultado ({$resposta->status()}).");
            }
        } catch (\Throwable $e) {
            // A resposta perde-se, mas o painel volta a por o run em falhado
            // pelo mecanismo dos pedidos presos. Nao vale a pena parar o worker.
            $this->error('  não consegui entregar o resultado: ' . $e->getMessage());
        }

        if (($payload['status'] ?? null) === 'failed') {
            $this->error('  falhou: ' . ($payload['error'] ?? ''));
        }
    }

    private function panel(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('claude.panel.url'), '/'))
            ->withToken((string) config('claude.panel.token'))
            ->acceptJson()
            ->timeout(60);
    }

    /**
     * O Claude Code precisa da chave quando corre em --bare ou sem login.
     * Devolver null deixa o processo herdar o ambiente do worker.
     */
    private function environment(): ?array
    {
        $chave = config('claude.api_key');

        return $chave ? ['ANTHROPIC_API_KEY' => $chave] : null;
    }
}
