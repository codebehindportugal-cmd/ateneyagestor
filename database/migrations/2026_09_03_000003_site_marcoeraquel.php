<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * marcoeraquel.pt — a loja que não estava no inventário.
 *
 * Só apareceu a 03/09/2026, quando o André escreveu o endereço de login de
 * cada site: o "Marco e Raquel" existia como projecto de sincronizador, mas a
 * loja em si nunca teve registo de Site, e portanto **nunca foi copiada para
 * o NAS**. Não é um site novo; é um site que já cá andava e não se via.
 *
 * Fica criado INACTIVO de propósito. O agente ainda não tem como entrar nesta
 * máquina — é um cPanel de alojamento (ipdsrvlx105.ipdroid.cloud:2083) e falta
 * a entrada em secrets.yaml. Activá-lo agora não fazia backups nenhuns; fazia
 * uma falha por noite e um aviso no telemóvel a dizer sempre o mesmo. A tarefa
 * que fica criada diz o que falta para o ligar.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $clientId = DB::table('clients')->where('email', 'marcoraquel.lda@gmail.com')->value('id')
            ?: DB::table('clients')->where('name', 'like', '%Marco%Raquel%')->value('id');

        if (! $clientId) {
            return;
        }

        $serverId = DB::table('servers')->where('name', 'ipdroid-marcoeraquel')->value('id');

        if (! $serverId) {
            // `servers` guarda so como la chegar. Desde a separacao de 27/08,
            // `type`, `domain` e os campos de cPanel (api_port, backup_dest, ...)
            // vivem em `sites`; aqui o painel instalado e' o `panel`.
            $serverId = DB::table('servers')->insertGetId([
                'client_id'        => $clientId,
                'name'             => 'ipdroid-marcoeraquel',
                'label'            => 'IPDroid — Marco e Raquel',
                'panel'            => 'cpanel',
                'is_active'        => false,
                'host'             => 'ipdsrvlx105.ipdroid.cloud',
                'agent_secret_ref' => 'ipdroid-marcoeraquel',
                'notes'            => "Alojamento cPanel da IPDroid. Painel em https://ipdsrvlx105.ipdroid.cloud:2083.\n\nInactivo até haver forma de o agente entrar: falta a entrada 'ipdroid-marcoeraquel' no secrets.yaml (token da API do cPanel, ou utilizador e chave SSH se o alojamento permitir).",
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }

        if (! DB::table('sites')->where('name', 'marcoeraquel-pt')->exists()) {
            DB::table('sites')->insert([
                'server_id'  => $serverId,
                'client_id'  => $clientId,
                'name'       => 'marcoeraquel-pt',
                'domain'     => 'marcoeraquel.pt',
                'login_url'  => 'https://marcoeraquel.pt/wp-admin',
                'type'       => 'cpanel',
                'is_active'  => false,
                'api_port'   => 2083,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->criarTarefa($now);
    }

    public function down(): void
    {
        DB::table('sites')->where('name', 'marcoeraquel-pt')->delete();
        DB::table('servers')->where('name', 'ipdroid-marcoeraquel')->delete();
    }

    private function criarTarefa(mixed $now): void
    {
        $projectId = DB::table('projects')->where('slug', 'backup-manager')->value('id');
        $andreId   = DB::table('users')->where('email', 'andre.f.mendes92@gmail.com')->value('id');
        $titulo    = 'Pôr marcoeraquel.pt na rota de backups';

        if (! $projectId || DB::table('project_tasks')->where('project_id', $projectId)->where('title', $titulo)->exists()) {
            return;
        }

        $position = (int) DB::table('project_tasks')->where('project_id', $projectId)->max('position') + 1;

        DB::table('project_tasks')->insert([
            'project_id'       => $projectId,
            'assigned_user_id' => $andreId,
            'title'            => $titulo,
            'description'      => <<<TXT
            Infra · A loja do Marco e Raquel nunca foi copiada. Descoberta a 03/09/2026 por acaso, ao registar os endereços de login: existia o projecto do sincronizador, mas não existia registo de Site nenhum — e o que não está em Sites não entra na rota do agente.

            O registo já está criado (servidor `ipdroid-marcoeraquel`, site `marcoeraquel-pt`), mas **inactivo**: o agente não tem como entrar nesta máquina.

            O QUE FALTA
            • Alojamento cPanel da IPDroid: https://ipdsrvlx105.ipdroid.cloud:2083
            • Confirmar se o alojamento dá acesso SSH. Se der, é o caminho mais simples — utilizador e chave, como nos outros.
            • Se não der, criar um token da API do cPanel e preencher `backup_dest`, `poll_interval_seconds` e `max_wait_seconds` no site.
            • Acrescentar a entrada `ipdroid-marcoeraquel` ao secrets.yaml do agente. Segredos não passam pelo painel.
            • Ligar o "Ativo" no servidor e no site, e forçar uma primeira corrida para confirmar.

            A REVER DE CAMINHO
            • Se esta loja escapou ao inventário, vale a pena confirmar que não há outra. Cruzar a lista de clientes com a lista de Sites e ver quem tem projecto mas não tem site.
            TXT,
            'status'          => 'pending',
            'position'        => $position,
            'estimated_hours' => 2,
            'created_by'      => $andreId,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
    }
};
