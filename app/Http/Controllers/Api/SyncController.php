<?php

namespace App\Http\Controllers\Api;

use App\Enums\SyncStatus;
use App\Http\Controllers\Controller;
use App\Models\SyncProject;
use App\Models\SyncRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backs the /api/sync/* routes called by phc_woo_sync, wintouch_woo_sync,
 * or any other external sync script.
 *
 * Each SyncProject issues a Sanctum token (via "Gerar token" in the admin
 * panel) that the Python/C# script sends as a Bearer token. This controller
 * verifies the token belongs to a SyncProject, not a User/Client/Agent.
 */
class SyncController extends Controller
{
    private function authenticatedProject(Request $request): SyncProject
    {
        $tokenable = $request->user();

        abort_unless($tokenable instanceof SyncProject, 403, 'Token nao pertence a um projeto de sincronizacao.');

        return $tokenable;
    }

    /**
     * POST /api/sync/runs
     *
     * Called once per sync execution, at the end. The script sends its
     * overall status, counts, and the full log text.
     *
     * Body:
     * {
     *   "status": "success|partial|failed",
     *   "products_synced": 150,
     *   "orders_synced": 5,
     *   "errors_count": 0,
     *   "started_at": "2026-06-20T12:00:00",
     *   "finished_at": "2026-06-20T12:05:00",
     *   "log": "...full log text...",
     *   "metadata": {}
     * }
     */
    public function storeRun(Request $request): JsonResponse
    {
        $project = $this->authenticatedProject($request);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:success,partial,failed'],
            'products_synced' => ['sometimes', 'integer', 'min:0'],
            'orders_synced' => ['sometimes', 'integer', 'min:0'],
            'errors_count' => ['sometimes', 'integer', 'min:0'],
            'started_at' => ['sometimes', 'date', 'nullable'],
            'finished_at' => ['sometimes', 'date', 'nullable'],
            'log' => ['sometimes', 'string', 'nullable'],
            'metadata' => ['sometimes', 'array', 'nullable'],
        ]);

        $run = SyncRun::create([
            'sync_project_id' => $project->id,
            'status' => $data['status'],
            'products_synced' => $data['products_synced'] ?? 0,
            'orders_synced' => $data['orders_synced'] ?? 0,
            'errors_count' => $data['errors_count'] ?? 0,
            'started_at' => $data['started_at'] ?? null,
            'finished_at' => $data['finished_at'] ?? null,
            'log' => $data['log'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $project->forceFill([
            'last_run_at' => now(),
            'status' => $data['status'] === 'failed' ? 'error' : 'ok',
        ])->save();

        return response()->json(['status' => 'ok', 'run_id' => $run->id]);
    }

    /**
     * GET /api/sync/should-run
     *
     * Polled by external runners (C# no cliente) para saber se alguém carregou
     * em "Correr agora" no painel. O pedido só é limpo quando o runner chama
     * /runs/start — se o arranque falhar, o pedido continua pendente.
     */
    public function shouldRun(Request $request): JsonResponse
    {
        $project = $this->authenticatedProject($request);

        return response()->json([
            'run_requested' => $project->is_active && $project->run_requested_at !== null,
            'requested_at' => $project->run_requested_at?->toIso8601String(),
        ]);
    }

    /**
     * POST /api/sync/runs/start
     *
     * Chamado pelo runner externo no INÍCIO da execução. Cria um SyncRun com
     * estado "running" (aparece "Em curso" no painel) e limpa o pedido de
     * execução remota. Devolve run_id para os endpoints /progress e /finish.
     *
     * Body opcional: { "metadata": {...} }
     */
    public function startRun(Request $request): JsonResponse
    {
        $project = $this->authenticatedProject($request);

        $data = $request->validate([
            'metadata' => ['sometimes', 'array', 'nullable'],
        ]);

        $run = SyncRun::create([
            'sync_project_id' => $project->id,
            'status' => SyncStatus::Running,
            'started_at' => now(),
            'log' => '['.now()->format('H:i:s')."] Execução iniciada pelo runner externo.\n",
            'metadata' => $data['metadata'] ?? null,
        ]);

        $project->forceFill(['run_requested_at' => null])->save();

        return response()->json(['status' => 'ok', 'run_id' => $run->id]);
    }

    /**
     * POST /api/sync/runs/{run}/progress
     *
     * Atualização periódica enquanto a sync decorre. Mantém o estado "running"
     * e guarda o progresso em metadata.progress (visível no painel).
     *
     * Body: { "processed": 120, "total": 900, "stage": "variacoes", "counts": {...} }
     */
    public function progressRun(Request $request, SyncRun $run): JsonResponse
    {
        $project = $this->authenticatedProject($request);

        abort_unless($run->sync_project_id === $project->id, 404);

        $data = $request->validate([
            'processed' => ['sometimes', 'integer', 'min:0'],
            'total' => ['sometimes', 'integer', 'min:0'],
            'stage' => ['sometimes', 'string', 'nullable', 'max:100'],
            'counts' => ['sometimes', 'array', 'nullable'],
        ]);

        $metadata = $run->metadata ?? [];
        $metadata['progress'] = [
            'processed' => $data['processed'] ?? null,
            'total' => $data['total'] ?? null,
            'stage' => $data['stage'] ?? null,
            'counts' => $data['counts'] ?? null,
            'updated_at' => now()->toIso8601String(),
        ];

        $run->update(['metadata' => $metadata]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * POST /api/sync/runs/{run}/finish
     *
     * Fecha um run criado por /runs/start com o resultado final.
     * Body: igual ao storeRun (status, counts, log, metadata...).
     */
    public function finishRun(Request $request, SyncRun $run): JsonResponse
    {
        $project = $this->authenticatedProject($request);

        abort_unless($run->sync_project_id === $project->id, 404);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:success,partial,failed'],
            'products_synced' => ['sometimes', 'integer', 'min:0'],
            'orders_synced' => ['sometimes', 'integer', 'min:0'],
            'errors_count' => ['sometimes', 'integer', 'min:0'],
            'started_at' => ['sometimes', 'date', 'nullable'],
            'finished_at' => ['sometimes', 'date', 'nullable'],
            'log' => ['sometimes', 'string', 'nullable'],
            'metadata' => ['sometimes', 'array', 'nullable'],
        ]);

        // Preserva metadata.progress final por baixo do metadata enviado no fim
        $metadata = $data['metadata'] ?? null;
        if ($metadata !== null && isset($run->metadata['progress']) && ! isset($metadata['progress'])) {
            $metadata['progress'] = $run->metadata['progress'];
        }

        $run->update([
            'status' => $data['status'],
            'products_synced' => $data['products_synced'] ?? $run->products_synced,
            'orders_synced' => $data['orders_synced'] ?? $run->orders_synced,
            'errors_count' => $data['errors_count'] ?? $run->errors_count,
            'finished_at' => $data['finished_at'] ?? now(),
            'log' => $data['log'] ?? $run->log,
            'metadata' => $metadata ?? $run->metadata,
        ]);

        $project->forceFill([
            'last_run_at' => now(),
            'status' => $data['status'] === 'failed' ? 'error' : 'ok',
        ])->save();

        return response()->json(['status' => 'ok', 'run_id' => $run->id]);
    }
}
