<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige o comando na tarefa WP-11.
 *
 * Dizia `python3 wp_user.py`, mas o agente corre num virtualenv em
 * /opt/backup-agent/venv — o python3 do sistema não tem o `requests` nem o
 * `yaml` e o comando morria à segunda linha. O atalho `contas.sh`, criado
 * pelo install.sh, já leva o interpretador certo.
 *
 * Vive numa migração à parte porque a tarefa pode já ter sido criada com o
 * texto errado num deploy anterior.
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
                    "cd /opt/backup-agent          # onde vive o agent_sync.py\n          python3 wp_user.py --revogar --confirmar",
                    'python3 wp_user.py',
                    '`wp_user.py --listar`',
                ],
                [
                    "cd /opt/backup-agent\n          ./contas.sh --revogar --confirmar",
                    './contas.sh',
                    '`./contas.sh --listar`',
                ],
                $texto
            );

            $novo = str_replace(
                'no LXC do Proxmox',
                'o CT 105 do Proxmox',
                $novo
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
