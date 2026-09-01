<?php

namespace App\Console\Commands;

use App\Enums\MonitorStatus;
use App\Models\Agent;
use App\Models\Server;
use App\Models\SiteMonitor;
use App\Models\SyncProject;
use App\Support\Ntfy;
use Illuminate\Console\Command;

/**
 * Resumo do estado, uma vez por dia.
 *
 * Os avisos normais so tocam em transicoes, o que e o que os torna suportaveis
 * — mas tem um custo: silencio pode significar "esta tudo bem" ou "isto deixou
 * de funcionar e nao te aviso disso". Este resumo diario resolve isso: se
 * chegar todos os dias, sabe-se que o sistema esta vivo; se deixar de chegar,
 * ha alguma coisa partida no proprio painel.
 */
class NtfyEstado extends Command
{
    protected $signature = 'ntfy:estado {--forcar : Enviar mesmo que esteja tudo bem}';

    protected $description = 'Envia para o telemovel um resumo do que esta em baixo';

    public function handle(): int
    {
        $sites = SiteMonitor::query()
            ->where('is_active', true)
            ->where('status', MonitorStatus::Down)
            ->pluck('name');

        $servidores = Server::query()
            ->where('ping_status', 'down')
            ->pluck('name');

        $syncs = SyncProject::query()
            ->where('is_active', true)
            ->where('status', 'error')
            ->pluck('name');

        $backups = Agent::query()
            ->where('last_backup_failed', '>', 0)
            ->get()
            ->map(fn (Agent $a) => ($a->name ?: $a->slug) . " ({$a->last_backup_failed}/{$a->last_backup_total})");

        $problemas = $sites->count() + $servidores->count() + $syncs->count() + $backups->count();

        $link = rtrim((string) config('app.url'), '/') . '/admin';

        if ($problemas === 0) {
            $this->info('Esta tudo de pe.');

            Ntfy::enviar(
                'teste',
                'Tudo de pe',
                'Sites, servidores, sincronizacoes e backups sem problemas.',
                tags: 'white_check_mark',
                link: $link,
            );

            return self::SUCCESS;
        }

        $linhas = [];

        if ($sites->isNotEmpty()) {
            $linhas[] = 'Sites em baixo: ' . $sites->implode(', ');
        }

        if ($servidores->isNotEmpty()) {
            $linhas[] = 'Servidores sem resposta: ' . $servidores->implode(', ');
        }

        if ($syncs->isNotEmpty()) {
            $linhas[] = 'Sincronizacoes em erro: ' . $syncs->implode(', ');
        }

        if ($backups->isNotEmpty()) {
            $linhas[] = 'Backups a falhar: ' . $backups->implode(', ');
        }

        $texto = implode("\n", $linhas);

        $this->warn($texto);

        Ntfy::enviar(
            'teste',
            $problemas === 1 ? '1 problema em aberto' : "{$problemas} problemas em aberto",
            $texto,
            prioridade: 'high',
            tags: 'warning',
            link: $link,
        );

        return self::SUCCESS;
    }
}
