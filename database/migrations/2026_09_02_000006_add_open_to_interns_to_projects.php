<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quais os projectos de onde um estagiário pode tirar trabalho.
 *
 * Sem isto, "tarefas sem dono" queria dizer *todas* as tarefas sem dono do
 * painel — incluindo as que só registam o estado de um projecto ("passar o
 * site para o novo servidor", "migrar para o domínio definitivo"). Essas são
 * apontamentos do André, não trabalho para distribuir, e apareciam no balcão
 * das tarefas por escolher como se fossem.
 *
 * O default é `false`: um projecto novo não fica aberto sem alguém o decidir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('open_to_interns')->default(false)->after('is_internal');
        });

        DB::table('projects')->where('slug', 'estagio-2026')->update(['open_to_interns' => true]);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('open_to_interns');
        });
    }
};
