<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige um estrago da migração 2026_09_24_000001.
 *
 * `site_monitor_checks.checked_at` foi criada como `timestamp()` não-nulo, e o
 * MySQL deu-lhe `ON UPDATE CURRENT_TIMESTAMP`. Quando a 000001 limpou o
 * `response_ms` das verificações falhadas, TODAS as falhas dos últimos 30 dias
 * ficaram com a data da migração (24/09/2026 10:45:07) e o "Uptime 24h" caiu
 * para ~30% em sites que estavam sempre de pé.
 *
 * A data original dessas linhas perdeu-se, por isso elas não servem para nada
 * e são apagadas. A coluna deixa de se actualizar sozinha.
 *
 * Também retira da monitorização os sites que o André removeu. A 000001 já
 * tinha corrido em produção antes de essa lista lá ser posta, por isso vai aqui.
 * Só sai o monitor; na lista de Sites ficam como estão, por decisão dele.
 */
return new class extends Migration
{
    private const REMOVIDOS = [
        'lojaamster.com',
        'sagovit.com',
        'clinicadosanjos.com',
        'terrasdeviriarte.com',
    ];

    public function up(): void
    {
        foreach (DB::table('site_monitors')->get(['id', 'url']) as $m) {
            $host = preg_replace('/^www\./', '', strtolower((string) parse_url($m->url, PHP_URL_HOST)));
            if (in_array($host, self::REMOVIDOS, true)) {
                DB::table('site_monitors')->where('id', $m->id)->delete(); // verificações vão em cascata
            }
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE site_monitor_checks MODIFY checked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        }

        // Todas as linhas estragadas têm a mesma data, ao segundo. As
        // verificações verdadeiras dessa hora têm segundos diferentes.
        $segundoEstragado = DB::table('site_monitor_checks')
            ->where('status', 'down')
            ->whereNull('http_code')
            ->select('checked_at', DB::raw('COUNT(*) AS n'))
            ->groupBy('checked_at')
            ->orderByDesc('n')
            ->first();

        if ($segundoEstragado && $segundoEstragado->n > 50) {
            DB::table('site_monitor_checks')
                ->where('checked_at', $segundoEstragado->checked_at)
                ->where('status', 'down')
                ->whereNull('http_code')
                ->delete();
        }
    }

    public function down(): void
    {
        // Nada a desfazer: as linhas apagadas já não tinham data válida.
    }
};
