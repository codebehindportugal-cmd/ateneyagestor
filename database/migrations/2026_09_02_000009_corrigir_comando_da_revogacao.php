<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige o comando na tarefa WP-11.
 *
 * A primeira versão mandava correr o `wp_user.py` no LXC do agente. Ficou
 * decidido fazer isto com um script que corre no próprio servidor
 * (`scripts/conta-estagio.sh`), sem depender do agente nem do Proxmox.
 *
 * Vive numa migração à parte porque a tarefa pode já ter sido criada com o
 * texto antigo num deploy anterior.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tarefas = DB::table('project_tasks')
            ->where('title', 'like', 'WP-11 %')
            ->get(['id', 'description']);

        foreach ($tarefas as $tarefa) {
            $texto = (string) $tarefa->description;

            $novo = str_replace(
                [
                    "(na máquina do agente, no LXC do Proxmox)\n          cd /opt/backup-agent          # onde vive o agent_sync.py\n          python3 wp_user.py --revogar --confirmar",
                    'python3 wp_user.py',
                    '`wp_user.py --listar`',
                ],
                [
                    "— em cada VPS, com o script scripts/conta-estagio.sh do repositório\n          scp scripts/conta-estagio.sh root@<vps>:/root/\n          ssh root@<vps> 'bash /root/conta-estagio.sh --revogar --confirmar'",
                    'bash conta-estagio.sh',
                    '`conta-estagio.sh --listar`',
                ],
                $texto
            );

            if ($novo !== $texto) {
                DB::table('project_tasks')
                    ->where('id', $tarefa->id)
                    ->update(['description' => $novo, 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // Sem volta atrás: o comando antigo estava simplesmente errado.
    }
};
