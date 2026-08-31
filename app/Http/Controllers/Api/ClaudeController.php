<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClaudeRun;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Support\ClaudeAgenda;
use App\Support\ClaudeTaskPrompt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Suporta as rotas /api/claude/*, chamadas pelo worker que corre na maquina
 * onde os repositorios estao (o PC do Andre).
 *
 * Mesmo sentido de conversa dos sincronizadores: o painel nunca chama a maquina
 * do worker — e o worker que vem buscar. E o que faz isto funcionar com o painel
 * em producao e o codigo noutro sitio, e o que deixa o worker mudar de casa (PC,
 * LXC no Proxmox, a propria VPS) sem se mexer no painel.
 *
 * O token e emitido a um User admin ("Token do worker" na listagem de
 * Projectos) e enviado como Bearer.
 */
class ClaudeController extends Controller
{
    private function authenticatedUser(Request $request): User
    {
        $tokenable = $request->user();

        abort_unless($tokenable instanceof User, 403, 'Token nao pertence a um utilizador do painel.');

        return $tokenable;
    }

    /**
     * O token do worker pode tudo; o da rotina da manha so pode ler a agenda e
     * por tarefas na fila.
     *
     * Existe porque esse segundo token vive fora da maquina do Andre — no texto
     * de uma tarefa agendada — e um token que so sabe fazer duas coisas e muito
     * menos mau de perder do que um que fecha execucoes e le respostas.
     */
    private function authenticatedForAgenda(Request $request): User
    {
        $user = $this->authenticatedUser($request);

        abort_unless(
            $request->user()->tokenCan('agenda') || $request->user()->tokenCan('*'),
            403,
            'Este token nao pode consultar a agenda.'
        );

        return $user;
    }

    /**
     * GET /api/claude/next
     *
     * Entrega o pedido mais antigo por fazer e marca-o como a correr, para dois
     * workers a apanhar ao mesmo tempo nao ficarem com o mesmo. Devolve
     * {"run": null} quando nao ha nada.
     */
    public function next(Request $request): JsonResponse
    {
        $this->authenticatedUser($request);

        $run = DB::transaction(function () {
            $run = ClaudeRun::where('status', 'queued')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            $run?->update([
                'status'     => 'running',
                'started_at' => now(),
                'error'      => null,
            ]);

            return $run;
        });

        if (! $run) {
            return response()->json(['run' => null]);
        }

        $run->load('task.project.client', 'task.project.server', 'task.project.site.server');

        $task    = $run->task;
        $project = $task?->project;

        if (! $task || ! $project) {
            $run->update([
                'status'      => 'failed',
                'error'       => 'A tarefa ou o projecto foram apagados entretanto.',
                'finished_at' => now(),
            ]);

            return response()->json(['run' => null]);
        }

        // A conversa anterior sobre a mesma tarefa, para o worker poder continuar
        // de onde ficou em vez de comecar do zero.
        $sessaoAnterior = ClaudeRun::where('project_task_id', $task->id)
            ->where('id', '!=', $run->id)
            ->whereNotNull('session_id')
            ->orderByDesc('id')
            ->value('session_id');

        return response()->json([
            'run' => [
                'id'                 => $run->id,
                'mode'               => $run->mode,
                'follow_up'          => $run->follow_up,
                'writes'             => $run->writes(),
                'previous_session_id' => $sessaoAnterior,
            ],
            'task' => [
                'id'    => $task->id,
                'title' => $task->title,
            ],
            'project' => [
                'name' => $project->name,
                'slug' => $project->slug,
            ],
            'code'        => $project->codeDescriptor(),
            'prompt_body' => ClaudeTaskPrompt::body($task),
        ]);
    }

    /**
     * GET /api/claude/agenda
     *
     * O que esta por fazer em todos os projectos, numa so chamada. Existe para a
     * rotina da manha nao ter de abrir o painel projecto a projecto no browser.
     *
     * So devolve titulos e estados — nada de credenciais, notas de servidor ou
     * caminhos. Aceita ?projecto={id} para limitar a um.
     */
    public function agenda(Request $request): JsonResponse
    {
        $this->authenticatedForAgenda($request);

        return response()->json(ClaudeAgenda::payload($request->integer('projecto') ?: null));
    }

    /**
     * POST /api/claude/tasks/{task}/queue
     *
     * Poe uma tarefa na fila sem passar pelos botoes do painel. E o que permite
     * a rotina da manha fazer o trabalho todo por API.
     */
    public function queue(Request $request, ProjectTask $task): JsonResponse
    {
        $user = $this->authenticatedForAgenda($request);

        $data = $request->validate([
            'mode'      => ['required', 'string', 'in:diagnose,continue,apply'],
            'follow_up' => ['nullable', 'string', 'max:20000', 'required_unless:mode,diagnose'],
        ]);

        // Nao vale a pena empilhar pedidos na mesma tarefa.
        if ($task->lastClaudeRun?->isPending() && ! $task->lastClaudeRun->isStale()) {
            return response()->json([
                'ok'   => false,
                'erro' => 'Já há um pedido por acabar nesta tarefa.',
            ], 409);
        }

        $run = ClaudeRun::create([
            'project_task_id' => $task->id,
            'status'          => 'queued',
            'mode'            => $data['mode'],
            'follow_up'       => $data['follow_up'] ?? null,
            'requested_by'    => $user->id,
        ]);

        return response()->json(['ok' => true, 'run_id' => $run->id]);
    }

    /**
     * POST /api/claude/runs/{run}/finish
     *
     * O worker fecha o pedido, com a resposta ou com o erro.
     */
    public function finish(Request $request, ClaudeRun $run): JsonResponse
    {
        $this->authenticatedUser($request);

        $data = $request->validate([
            'status'      => ['required', 'string', 'in:done,failed'],
            'result'      => ['nullable', 'string'],
            'error'       => ['nullable', 'string'],
            'prompt'      => ['nullable', 'string'],
            'session_id'  => ['nullable', 'string', 'max:255'],
            'cost_usd'    => ['nullable', 'numeric', 'min:0'],
            'duration_ms' => ['nullable', 'integer', 'min:0'],
        ]);

        $run->update([
            'status'      => $data['status'],
            'result'      => $data['result'] ?? null,
            'error'       => $data['error'] ?? null,
            'prompt'      => $data['prompt'] ?? $run->prompt,
            'session_id'  => $data['session_id'] ?? null,
            'cost_usd'    => $data['cost_usd'] ?? null,
            'duration_ms' => $data['duration_ms'] ?? null,
            'finished_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
