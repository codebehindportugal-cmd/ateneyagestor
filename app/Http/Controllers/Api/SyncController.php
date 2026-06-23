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
}
