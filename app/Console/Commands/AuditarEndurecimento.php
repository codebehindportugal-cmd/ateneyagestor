<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\Seguranca\AuditoriaEndurecimento;
use Illuminate\Console\Command;

/**
 * php artisan seguranca:auditar                 (pergunta qual)
 * php artisan seguranca:auditar 3               (um servidor)
 * php artisan seguranca:auditar --todos         (todos os activos)
 */
class AuditarEndurecimento extends Command
{
    protected $signature = 'seguranca:auditar
                            {servidor? : Id ou nome do servidor}
                            {--todos : Correr em todos os servidores activos}';

    protected $description = 'Verifica o endurecimento (SSH, firewall, updates, Apache, PHP) dos servidores';

    public function handle(AuditoriaEndurecimento $auditoria): int
    {
        $servidores = $this->escolherServidores();

        if ($servidores->isEmpty()) {
            $this->error('Não encontrei esse servidor.');

            return self::FAILURE;
        }

        $problemas = 0;

        foreach ($servidores as $servidor) {
            $this->newLine();
            $this->info("── {$servidor->name} ({$servidor->host})");

            $resultado = $auditoria->correr($servidor, 'comando');

            if ($resultado->estado === 'erro') {
                $this->error("   erro: {$resultado->erro}");
                $problemas++;

                continue;
            }

            foreach ($resultado->resultados ?? [] as $r) {
                $sinal = match ($r['estado']) {
                    'ok'    => '<fg=green>ok   </>',
                    'aviso' => '<fg=yellow>aviso</>',
                    'falha' => '<fg=red>FALHA</>',
                    default => '<fg=gray>info </>',
                };

                $this->line("   {$sinal} {$r['label']}");

                if ($r['estado'] !== 'ok' && filled($r['detalhe'])) {
                    foreach (explode("\n", $r['detalhe']) as $linha) {
                        $this->line("         <fg=gray>{$linha}</>");
                    }
                }
            }

            $this->line("   → {$resultado->falhas} por corrigir, {$resultado->avisos} avisos, de {$resultado->total}");

            $problemas += $resultado->falhas;
        }

        $this->newLine();
        $this->line($problemas === 0
            ? 'Nada a apontar.'
            : "Total: {$problemas} coisas por corrigir. No painel, em Infraestrutura → Endurecimento, cada uma tem botão.");

        return self::SUCCESS;
    }

    private function escolherServidores()
    {
        if ($this->option('todos')) {
            return Server::where('is_active', true)->orderBy('name')->get();
        }

        $procura = $this->argument('servidor');

        if (! $procura) {
            $escolha = $this->choice(
                'Qual servidor?',
                Server::where('is_active', true)->orderBy('name')->pluck('name')->all(),
            );

            return Server::where('name', $escolha)->get();
        }

        return Server::where('id', $procura)->orWhere('name', $procura)->get();
    }
}
