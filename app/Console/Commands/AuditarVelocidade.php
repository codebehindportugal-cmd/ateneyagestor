<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\Velocidade\AuditoriaVelocidade;
use Illuminate\Console\Command;

/**
 * php artisan velocidade:auditar 7          (um servidor, por id ou nome)
 * php artisan velocidade:auditar --todos    (todos os activos)
 *
 * Corre na hora (sem fila). O resultado fica também no painel, em
 * Infraestrutura → Velocidade, com os botões de corrigir.
 */
class AuditarVelocidade extends Command
{
    protected $signature = 'velocidade:auditar
                            {servidor? : Id ou nome do servidor}
                            {--todos : Todos os servidores activos}';

    protected $description = 'Analisa porque é que os sites de um servidor são lentos (OPcache, cache de página, Redis, MySQL...)';

    public function handle(AuditoriaVelocidade $servico): int
    {
        $servidores = $this->option('todos')
            ? Server::where('is_active', true)->orderBy('name')->get()
            : Server::where('id', $this->argument('servidor'))->orWhere('name', $this->argument('servidor'))->get();

        if ($servidores->isEmpty()) {
            $this->error('Não encontrei esse servidor.');

            return self::FAILURE;
        }

        foreach ($servidores as $servidor) {
            $this->newLine();
            $this->info("── {$servidor->name} ({$servidor->host})");

            $auditoria = $servico->correr(AuditoriaVelocidade::nova($servidor, 'comando'));

            if ($auditoria->estado === 'erro') {
                $this->error("   erro: {$auditoria->erro}");

                continue;
            }

            foreach ($auditoria->porGrupo() as $grupo => $linhas) {
                $this->line("   <options=bold>{$grupo}</>");
                foreach ($linhas as $r) {
                    $sinal = match ($r['estado']) {
                        'ok'    => '<fg=green>ok   </>',
                        'aviso' => '<fg=yellow>aviso</>',
                        'falha' => '<fg=red>FALHA</>',
                        default => '<fg=gray>info </>',
                    };
                    $this->line("     {$sinal} {$r['label']} — " . strtok($r['detalhe'], "\n"));
                }
            }

            $this->line("   → {$auditoria->falhas} por corrigir, {$auditoria->avisos} avisos. Painel: Infraestrutura → Velocidade.");
        }

        return self::SUCCESS;
    }
}
