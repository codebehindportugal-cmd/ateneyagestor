<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Estimativa de tempo, separada das horas realmente gastas.
 *
 * O campo `hours` que já existia é o que se regista depois — o que se factura.
 * Encher esse com palpites estragava a única coluna do painel que diz a
 * verdade sobre o tempo que uma coisa custou. São duas perguntas diferentes
 * ("quanto achamos" e "quanto foi"), e é da diferença entre as duas que se
 * aprende a estimar.
 */
return new class extends Migration
{
    /**
     * As estimativas dos cartões do quadro. Ficam pela chave (WP-03, LR-07…)
     * e não pelo título todo, para uma vírgula mudada não desalinhar tudo.
     */
    private const ESTIMATIVAS = [
        'WP-00' => 4,  'WP-01' => 6,  'WP-02' => 8,  'WP-03' => 6,
        'WP-04' => 5,  'WP-05' => 8,  'WP-06' => 6,  'WP-07' => 5,
        'WP-08' => 6,  'WP-09' => 8,  'WP-10' => 10, 'WP-11' => 1,
        'LR-01' => 4,  'LR-02' => 3,  'LR-03' => 6,  'LR-04' => 8,
        'LR-05' => 6,  'LR-06' => 5,  'LR-07' => 5,  'LR-08' => 6,
        'LR-09' => 6,  'LR-10' => 8,
    ];

    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->decimal('estimated_hours', 6, 2)->nullable()->after('hours');
        });

        $projectId = DB::table('projects')->where('slug', 'estagio-2026')->value('id');

        if (! $projectId) {
            return;
        }

        foreach (self::ESTIMATIVAS as $chave => $horas) {
            DB::table('project_tasks')
                ->where('project_id', $projectId)
                ->where('title', 'like', $chave . ' %')
                ->whereNull('estimated_hours')
                ->update(['estimated_hours' => $horas]);
        }
    }

    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropColumn('estimated_hours');
        });
    }
};
