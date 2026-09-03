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

        // O buraco que deixou passar quatro dias sem backups (29/08 a 02/09
        // de 2026): olhava-se para backups que FALHARAM, nunca para backups
        // que deixaram de acontecer. O agente de casa morreu com 24/24 ok, o
        // contador de falhas ficou a zero, e este resumo disse "tudo de pe"
        // todas as manhas enquanto nada era copiado.
        //
        // Um agente que nunca deu sinal na vida fica de fora: e um agente
        // configurado e por instalar, nao uma regressao, e apareceria aqui
        // todos os dias sem nada de novo a dizer.
        $mudos = Agent::query()
            ->whereNotNull('last_seen_at')
            ->get()
            ->filter(fn (Agent $a) => $a->status === 'offline' || $a->backupIsStale())
            ->map(function (Agent $a) {
                $nome  = $a->name ?: $a->slug;
                $desde = $a->last_seen_at?->diffForHumans() ?? 'ha muito';

                return "{$nome} (ultimo contacto {$desde})";
            })
            ->values();

        $problemas = $sites->count() + $servidores->count() + $syncs->count()
            + $backups->count() + $mudos->count();

        $link = rtrim((string) config('app.url'), '/') . '/admin';

        if ($problemas === 0) {
            $this->info('Esta tudo de pe.');

            Ntfy::enviar(
                'teste',
                'Tudo de pe',
                'Sites, servidores, sincronizacoes e backups sem problemas, e os agentes a dar sinal.',
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

        // Primeiro na mensagem: um agente calado e pior do que um backup
        // falhado, porque nao ha nada nenhum a ser copiado.
        if ($mudos->isNotEmpty()) {
            array_unshift($linhas, 'SEM BACKUPS — agente parado: ' . $mudos->implode(', '));
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
