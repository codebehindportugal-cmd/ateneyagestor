<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Descobre como arrancar o Claude Code nesta maquina.
 *
 * Em Windows ha tres caminhos e nem todos funcionam: o .cmd do npm falha quando
 * o caminho leva espacos ou acentos (o "André Mendes" da pasta do utilizador
 * chega ao cmd noutra pagina de codigo e nao resolve). Este comando tenta os
 * tres, diz qual pega, e escreve a linha exacta para o .env.
 *
 *   php artisan claude:check
 */
class ClaudeCheck extends Command
{
    protected $signature = 'claude:check';

    protected $description = 'Descobre como arrancar o Claude Code nesta máquina';

    public function handle(): int
    {
        $this->line('');
        $this->info('A procurar o Claude Code…');
        $this->line('');

        $candidatos = $this->candidates();
        $vencedor   = null;

        foreach ($candidatos as $nome => $comando) {
            $resultado = $this->try($comando);

            if ($resultado['ok']) {
                $this->line("  <fg=green>✔</> {$nome} — {$resultado['saida']}");
                $vencedor ??= [$nome, $comando];
            } else {
                $this->line("  <fg=red>✘</> {$nome} — {$resultado['saida']}");
            }
        }

        $this->line('');

        if (! $vencedor) {
            $this->error('Nenhum dos caminhos funcionou.');
            $this->line('Confirma que o Claude Code está instalado: npm install -g @anthropic-ai/claude-code');
            $this->line('Depois corre `where claude` e diz o que aparece.');

            return 1;
        }

        [$nome, $comando] = $vencedor;

        $this->info("Usa este: {$nome}");
        $this->line('');

        if ($nome === 'claude (do PATH)') {
            $this->line('  Não precisas de nada no .env — é o valor por defeito.');
            $this->line('  Se tiveres uma linha CLAUDE_BINARY, comenta-a.');
        } elseif (str_starts_with($nome, 'node')) {
            $this->line('  No .env desta máquina, com aspas simples:');
            $this->line('');
            $this->line("  CLAUDE_NODE_SCRIPT='" . $comando[1] . "'");
        } else {
            $this->line('  No .env desta máquina, com aspas simples:');
            $this->line('');
            $this->line("  CLAUDE_BINARY='" . $comando[0] . "'");
        }

        $this->line('');
        $this->line('Depois: php artisan config:clear && php artisan claude:work --once');
        $this->line('');

        return 0;
    }

    /** @return array<string, list<string>> */
    private function candidates(): array
    {
        $candidatos = ['claude (do PATH)' => ['claude']];

        if ($configurado = trim((string) config('claude.binary'), " \t\"'")) {
            if ($configurado !== 'claude') {
                $candidatos["CLAUDE_BINARY ({$configurado})"] = [$configurado];
            }
        }

        foreach ($this->nodeScriptCandidates() as $script) {
            if (is_file($script)) {
                $candidatos["node + cli.js"] = ['node', $script];
                break;
            }
        }

        return $candidatos;
    }

    /** @return list<string> */
    private function nodeScriptCandidates(): array
    {
        $caminhos = [];

        if ($configurado = trim((string) config('claude.node_script'), " \t\"'")) {
            $caminhos[] = $configurado;
        }

        $sufixo = DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR
            . '@anthropic-ai' . DIRECTORY_SEPARATOR . 'claude-code' . DIRECTORY_SEPARATOR . 'cli.js';

        foreach ([getenv('APPDATA'), getenv('HOME'), '/usr/lib/node_modules/..', '/usr/local/lib/node_modules/..'] as $base) {
            if ($base) {
                $caminhos[] = rtrim($base, '\\/') . DIRECTORY_SEPARATOR . 'npm' . $sufixo;
                $caminhos[] = rtrim($base, '\\/') . $sufixo;
            }
        }

        return $caminhos;
    }

    /**
     * @param  list<string>  $comando
     * @return array{ok: bool, saida: string}
     */
    private function try(array $comando): array
    {
        try {
            $processo = new Process([...$comando, '--version']);
            $processo->setTimeout(30);
            $processo->run();
        } catch (\Throwable $e) {
            return ['ok' => false, 'saida' => $e->getMessage()];
        }

        $saida = trim($processo->getOutput()) ?: trim($processo->getErrorOutput());

        return [
            'ok'    => $processo->isSuccessful() && $saida !== '',
            'saida' => mb_substr($saida ?: 'sem resposta (código ' . $processo->getExitCode() . ')', 0, 160),
        ];
    }
}
