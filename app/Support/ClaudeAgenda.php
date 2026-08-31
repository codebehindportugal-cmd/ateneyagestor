<?php

namespace App\Support;

use App\Models\Project;
use App\Models\ProjectTask;

/**
 * O que esta por fazer em todos os projectos, num so sitio.
 *
 * Serve duas portas: a API (com token, para quem tem rede ate ao painel) e uma
 * pagina do painel protegida pela sessao (para a rotina da manha, que so la
 * chega pelo browser). Fica aqui para as duas nao divergirem.
 */
class ClaudeAgenda
{
    public static function payload(?int $projecto = null): array
    {
        $projectos = Project::query()
            ->with(['tasks' => fn ($q) => $q
                ->whereNotIn('status', ['done', 'cancelled'])
                ->with('lastClaudeRun')
                ->orderBy('position')])
            ->when($projecto, fn ($q, $id) => $q->where('id', $id))
            ->where('status', '!=', 'suspended')
            ->orderBy('name')
            ->get()
            ->filter(fn (Project $p) => $p->tasks->isNotEmpty())
            ->values();

        $linhas = $projectos->map(fn (Project $p) => [
            'id'           => $p->id,
            'nome'         => $p->name,
            'estado'       => $p->statusLabel(),
            'fonte_codigo' => $p->code_source,
            'tem_codigo'   => $p->hasCode(),
            'url'          => $p->url,
            'tarefas'      => $p->tasks->map(fn (ProjectTask $t) => [
                'id'              => $t->id,
                'titulo'          => $t->title,
                'notas'           => $t->description,
                'estado'          => $t->status,
                'estado_label'    => $t->statusLabel(),
                'prazo'           => $t->due_date?->toDateString(),
                'atrasada'        => $t->isOverdue(),
                'ja_respondida'   => $t->lastClaudeRun?->isDone() === true,
                'pedido_pendente' => $t->lastClaudeRun?->isPending() === true,
            ])->values(),
        ]);

        $tarefas = $linhas->flatMap(fn ($p) => $p['tarefas']);

        return [
            'gerado_em' => now()->toIso8601String(),
            'resumo'    => [
                'projectos'          => $linhas->count(),
                'tarefas'            => $tarefas->count(),
                'atrasadas'          => $tarefas->where('atrasada', true)->count(),
                'a_aguardar_cliente' => $tarefas->where('estado', 'waiting_client')->count(),
                'ja_na_fila'         => $tarefas->where('pedido_pendente', true)->count(),
            ],
            'projectos' => $linhas,
        ];
    }
}
