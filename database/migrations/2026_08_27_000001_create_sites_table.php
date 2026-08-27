<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separa "servidor" de "site".
 *
 * Até aqui cada linha de `servers` era na prática um domínio: quatro sites no
 * mesmo VPS obrigavam a quatro linhas a repetir host, porta, utilizador e
 * referência da chave. Mudar o IP de uma máquina obrigava a editar N registos,
 * e o mesmo VPS aparecia N vezes na listagem.
 *
 * A partir daqui:
 *   servers  = a máquina    (como lá chegar: host, porta, utilizador, chave)
 *   sites    = o que copiar (domínio, tipo, caminhos, retenção)
 *
 * A migração agrupa os registos existentes por (host, porta, utilizador) — a
 * mesma ligação é a mesma máquina — mantém uma linha de servidor por grupo e
 * transforma todas as antigas em sites. O histórico de backups é reapontado
 * antes de qualquer apagar, para não se perder nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            // Pasta no NAS, dentro da pasta do servidor: <servidor>/<site>/<data>
            $table->string('name')->unique();
            $table->string('domain')->nullable();
            $table->string('type'); // wordpress | plesk | vps_laravel | cpanel
            $table->boolean('is_active')->default(true);

            // wordpress
            $table->string('wp_root')->nullable();

            // vps_laravel
            $table->string('app_path')->nullable();
            $table->json('storage_paths')->nullable();
            $table->json('db_override')->nullable();

            // plesk
            $table->json('plesk_backup_args')->nullable();

            // cpanel
            $table->unsignedInteger('api_port')->nullable();
            $table->string('backup_dest')->nullable();
            $table->unsignedInteger('poll_interval_seconds')->nullable();
            $table->unsignedInteger('max_wait_seconds')->nullable();

            // Retenção própria; em branco usa a do servidor, e depois a do agente.
            $table->unsignedInteger('retention_keep_days')->nullable();
            $table->unsignedInteger('retention_keep_min_copies')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'is_active']);
        });

        Schema::table('backup_runs', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('server_id')
                ->constrained()->nullOnDelete();
        });

        Schema::table('servers', function (Blueprint $table) {
            // Nome legível da máquina ("Contabo B"), separado do identificador.
            $table->string('label')->nullable()->after('name');
            // Painel de controlo instalado, se algum. Decide se o backup de um
            // site deste servidor pode usar pleskbackup.
            $table->string('panel')->nullable()->after('label');
        });

        $this->moveSiteDataOutOfServers();

        Schema::table('servers', function (Blueprint $table) {
            // Passaram todas para `sites`.
            $table->dropColumn([
                'type',
                'domain',
                'wp_root',
                'app_path',
                'storage_paths',
                'db_override',
                'plesk_backup_args',
                'api_port',
                'backup_dest',
                'poll_interval_seconds',
                'max_wait_seconds',
            ]);
        });
    }

    /**
     * Uma linha de servidor por ligação distinta; tudo o resto vira site.
     */
    private function moveSiteDataOutOfServers(): void
    {
        $servers = DB::table('servers')->orderBy('id')->get();

        if ($servers->isEmpty()) {
            return;
        }

        $keyOf = fn ($server) => implode('|', [
            $server->host,
            $server->port ?: 22,
            $server->user ?: 'root',
        ]);

        // O registo mais antigo de cada ligação fica a ser a máquina.
        $canonical = [];
        foreach ($servers as $server) {
            $canonical[$keyOf($server)] ??= $server->id;
        }

        foreach ($servers as $server) {
            $serverId = $canonical[$keyOf($server)];

            $siteId = DB::table('sites')->insertGetId([
                'server_id'                 => $serverId,
                'client_id'                 => $server->client_id,
                'name'                      => $server->name,
                'domain'                    => $server->domain,
                'type'                      => $server->type,
                'is_active'                 => $server->is_active,
                'wp_root'                   => $server->wp_root,
                'app_path'                  => $server->app_path,
                'storage_paths'             => $server->storage_paths,
                'db_override'               => $server->db_override,
                'plesk_backup_args'         => $server->plesk_backup_args,
                'api_port'                  => $server->api_port,
                'backup_dest'               => $server->backup_dest,
                'poll_interval_seconds'     => $server->poll_interval_seconds,
                'max_wait_seconds'          => $server->max_wait_seconds,
                'retention_keep_days'       => $server->retention_keep_days,
                'retention_keep_min_copies' => $server->retention_keep_min_copies,
                'notes'                     => $server->notes,
                'created_at'                => $server->created_at,
                'updated_at'                => now(),
            ]);

            // Reaponta o histórico ANTES de apagar seja o que for.
            DB::table('backup_runs')
                ->where('server_id', $server->id)
                ->update(['site_id' => $siteId, 'server_id' => $serverId]);

            foreach (['site_monitors', 'security_scans'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'server_id')) {
                    DB::table($table)
                        ->where('server_id', $server->id)
                        ->update(['server_id' => $serverId]);
                }
            }

            if ($server->id === $serverId && $server->type === 'plesk') {
                DB::table('servers')->where('id', $serverId)->update(['panel' => 'plesk']);
            }
        }

        // Um servidor cujo registo canónico não era Plesk pode mesmo assim ter
        // sites Plesk (aconteceu na migração real): marca-o pelo que os sites dizem.
        $comPlesk = DB::table('sites')->where('type', 'plesk')->distinct()->pluck('server_id');
        DB::table('servers')->whereIn('id', $comPlesk)->update(['panel' => 'plesk']);

        // As linhas que eram domínios disfarçados de servidor.
        DB::table('servers')->whereNotIn('id', array_values($canonical))->delete();

        // O nome passa a identificar a máquina. Fica genérico de propósito —
        // é para ser renomeado no painel ("Contabo B", "Horta", ...).
        foreach (DB::table('servers')->get() as $server) {
            DB::table('servers')->where('id', $server->id)->update([
                'name'  => 'vps-' . str_replace('.', '-', $server->host),
                'label' => $server->label ?: $server->name,
            ]);
        }
    }

    public function down(): void
    {
        // Sem volta atrás fiel: os sites de um servidor com vários domínios não
        // cabem numa única linha de `servers`. Repõe a estrutura, não os dados.
        Schema::table('servers', function (Blueprint $table) {
            $table->string('type')->nullable();
            $table->string('domain')->nullable();
            $table->string('wp_root')->nullable();
            $table->string('app_path')->nullable();
            $table->json('storage_paths')->nullable();
            $table->json('db_override')->nullable();
            $table->json('plesk_backup_args')->nullable();
            $table->unsignedInteger('api_port')->nullable();
            $table->string('backup_dest')->nullable();
            $table->unsignedInteger('poll_interval_seconds')->nullable();
            $table->unsignedInteger('max_wait_seconds')->nullable();
            $table->dropColumn(['label', 'panel']);
        });

        Schema::table('backup_runs', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropColumn('site_id');
        });

        Schema::dropIfExists('sites');
    }
};
