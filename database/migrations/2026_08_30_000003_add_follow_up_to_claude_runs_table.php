<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A instrucao que o Andre escreve para continuar uma conversa.
 *
 * Sem isto o botao so sabia mandar o enunciado da tarefa, e por isso cada
 * clique repetia o mesmo diagnostico em vez de avancar. Com o follow_up
 * guardado, o worker retoma a sessao anterior (--resume) e manda so a
 * instrucao nova — que e o que se faz numa conversa a serio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claude_runs', function (Blueprint $table) {
            $table->text('follow_up')->nullable()->after('mode');
        });
    }

    public function down(): void
    {
        Schema::table('claude_runs', function (Blueprint $table) {
            $table->dropColumn('follow_up');
        });
    }
};
