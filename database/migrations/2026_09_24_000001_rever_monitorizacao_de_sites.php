<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Revisão da lista de monitorização (24/09/2026).
 *
 * 1. Novas colunas: TTFB (tempo até ao primeiro byte) e URL final depois de
 *    redirects — o "Tempo" sozinho misturava servidor lento com redirects.
 * 2. Nomes com gralhas corrigidos (alorfisconta, frutaalvor, terrasdviriarte…)
 *    e nomes soltos alinhados com o domínio.
 * 3. Cada monitor passa a apontar para o servidor (máquina) do Site com o
 *    mesmo domínio — os monitores foram criados em 06/2026 a partir da tabela
 *    servers de quando cada "servidor" era na verdade um domínio.
 * 4. Sites de produção que estavam em Sites mas não eram monitorizados passam
 *    a ser. Ficam de fora os de desenvolvimento (*.plesk.page) e o próprio
 *    gestao.ateneya.com (se ele cair, não há quem o verifique).
 */
return new class extends Migration
{
    private const NOMES = [
        'alvorfisconta.com'   => 'alvorfisconta-com',
        'frutalvor.com'       => 'frutalvor-com',
        'terrasdeviriarte.com' => 'terrasdeviriarte-com',
        'taxis.codebehind.pt' => 'taxis-codebehind-pt',
        'hortadamaria.com'    => 'hortadamaria-com',
        'faustinoclemente.pt' => 'faustinoclemente-pt',
        'lojaamster.com'      => 'lojaamster-com',
        'lovinbookguesthouse.com' => 'lovinbookguesthouse-com',
        'sagovit.com'         => 'sagovit-com',
    ];

    public function up(): void
    {
        Schema::table('site_monitors', function (Blueprint $table) {
            if (! Schema::hasColumn('site_monitors', 'last_ttfb_ms')) {
                $table->unsignedInteger('last_ttfb_ms')->nullable()->after('last_response_ms');
            }
            if (! Schema::hasColumn('site_monitors', 'last_final_url')) {
                $table->string('last_final_url', 500)->nullable()->after('last_ttfb_ms');
            }
        });

        Schema::table('site_monitor_checks', function (Blueprint $table) {
            if (! Schema::hasColumn('site_monitor_checks', 'ttfb_ms')) {
                $table->unsignedInteger('ttfb_ms')->nullable()->after('response_ms');
            }
        });

        // Timeouts antigos gravaram ~15000 ms como "tempo de resposta" e
        // puxavam as médias para cima. Uma verificação falhada sem código HTTP
        // não teve resposta: o tempo dela não é tempo de resposta.
        DB::table('site_monitor_checks')->where('status', 'down')->whereNull('http_code')->update(['response_ms' => null]);
        DB::table('site_monitors')->where('status', 'down')->whereNull('last_http_code')->update(['last_response_ms' => null]);

        $sites = DB::table('sites')->get()->keyBy(fn ($s) => $this->host($s->domain));

        foreach (DB::table('site_monitors')->get() as $m) {
            $host    = $this->host($m->url);
            $changes = [];

            if (isset(self::NOMES[$host])) {
                $changes['name'] = self::NOMES[$host];
            }

            if ($site = $sites->get($host)) {
                if ($site->server_id && $site->server_id != $m->server_id) {
                    $changes['server_id'] = $site->server_id;
                }
                if (! $m->client_id && $site->client_id) {
                    $changes['client_id'] = $site->client_id;
                }
            }

            if ($changes) {
                DB::table('site_monitors')->where('id', $m->id)->update($changes + ['updated_at' => now()]);
            }
        }

        $monitorizados = DB::table('site_monitors')->pluck('url')->map(fn ($u) => $this->host($u))->flip();

        foreach ($sites as $host => $site) {
            if (! $site->is_active || $host === '' || isset($monitorizados[$host])) {
                continue;
            }
            if (str_ends_with($host, '.plesk.page') || $host === 'gestao.ateneya.com') {
                continue;
            }

            DB::table('site_monitors')->insert([
                'client_id'  => $site->client_id,
                'server_id'  => $site->server_id,
                'name'       => $site->name,
                'url'        => 'https://' . $host,
                'is_active'  => true,
                'notify'     => true,
                'status'     => 'unknown',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('site_monitor_checks', function (Blueprint $table) {
            $table->dropColumn('ttfb_ms');
        });
        Schema::table('site_monitors', function (Blueprint $table) {
            $table->dropColumn(['last_ttfb_ms', 'last_final_url']);
        });
    }

    private function host(?string $urlOuDominio): string
    {
        $v = trim((string) $urlOuDominio);
        if ($v === '') {
            return '';
        }
        $h = parse_url(str_contains($v, '://') ? $v : "https://{$v}", PHP_URL_HOST) ?: $v;

        return preg_replace('/^www\./', '', strtolower($h));
    }
};
