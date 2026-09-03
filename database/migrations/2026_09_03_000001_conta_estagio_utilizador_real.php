<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A conta ficou criada com o login `estagio2026`, não `estagio`.
 *
 * Parece um pormenor e não é: no dia da revogação o script procura a conta
 * pelo nome, e um nome errado devolve "não existia" em todos os sites — o que
 * daria a entender que estava tudo limpo com a conta ainda lá viva.
 */
return new class extends Migration
{
    public function up(): void
    {
        $projectId = DB::table('projects')->where('slug', 'estagio-2026')->value('id');

        if ($projectId) {
            $notas = (string) DB::table('projects')->where('id', $projectId)->value('notes');

            $novas = str_replace(
                'Conta partilhada pelos três: estagio@codebehind.pt — papel Administrador.',
                'Conta partilhada pelos três: utilizador **estagio2026**, email estagio@codebehind.pt — papel Administrador.',
                $notas
            );

            if ($novas !== $notas) {
                DB::table('projects')->where('id', $projectId)->update([
                    'notes'      => $novas,
                    'updated_at' => now(),
                ]);
            }
        }

        foreach (DB::table('project_tasks')->where('title', 'like', 'WP-11 %')->get(['id', 'description']) as $tarefa) {
            $texto = (string) $tarefa->description;

            $novo = str_replace(
                'Isto apaga estagio@codebehind.pt de todos os WordPress',
                'Isto apaga a conta estagio2026 (estagio@codebehind.pt) de todos os WordPress',
                $texto
            );

            if ($novo !== $texto) {
                DB::table('project_tasks')->where('id', $tarefa->id)->update([
                    'description' => $novo,
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Sem volta atrás: o nome antigo estava simplesmente errado.
    }
};
