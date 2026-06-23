<?php

namespace App\Http\Controllers\Api;

use App\Enums\BackupStatus;
use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\BackupRun;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Backs the /api/agent/* routes called by agent_sync.py on the Pi.
 *
 * auth:sanctum resolves the bearer token to whichever model it belongs to
 * (User, Client, or Agent all use HasApiTokens) -- every method here
 * double-checks the token actually belongs to an Agent, so a leaked
 * admin/client token can never be used to hit these endpoints, and vice
 * versa an agent token can't be used anywhere else.
 */
class AgentController extends Controller
{
    private function authenticatedAgent(Request $request): Agent
    {
        $user = $request->user();

        abort_unless($user instanceof Agent, 403, 'This token is not an agent token.');

        return $user;
    }

    /**
     * GET /api/agent/config
     *
     * Returns this agent's servers (metadata only -- no secrets, ever) plus
     * its global settings. Shape matches what agent_sync.py's build_config()
     * expects, which in turn matches pi_backup/config.py's schema.
     */
    public function config(Request $request): JsonResponse
    {
        $agent = $this->authenticatedAgent($request);

        $servers = Server::query()
            ->where('is_active', true)
            ->where(function ($query) use ($agent) {
                $query->whereNull('agent_id')->orWhere('agent_id', $agent->id);
            })
            ->get();

        return response()->json([
            'global' => $agent->toAgentGlobalArray(),
            'servers' => $servers->map(fn (Server $server) => $server->toAgentArray())->values(),
        ]);
    }

    /**
     * POST /api/agent/runs
     *
     * Body: { results: [{name, type, success, error, started_at, finished_at}],
     *         merge_errors: [string], dry_run: bool }
     * (this is exactly what backup.py's --results-json produces, passed
     * through by agent_sync.py).
     *
     * A server name that no longer matches any record (e.g. renamed or
     * deleted on the website after the Pi already fetched its config) is
     * skipped and logged, not fatal to the rest of the batch.
     */
    public function storeRunResults(Request $request): JsonResponse
    {
        $agent = $this->authenticatedAgent($request);

        $data = $request->validate([
            'dry_run' => ['sometimes', 'boolean'],
            'results' => ['required', 'array'],
            'results.*.name' => ['required', 'string'],
            'results.*.type' => ['sometimes', 'string', 'nullable'],
            'results.*.success' => ['required', 'boolean'],
            'results.*.error' => ['sometimes', 'string', 'nullable'],
            'results.*.started_at' => ['sometimes', 'date', 'nullable'],
            'results.*.finished_at' => ['sometimes', 'date', 'nullable'],
            'merge_errors' => ['sometimes', 'array'],
        ]);

        if ($data['dry_run'] ?? false) {
            // Dry runs are intentionally never persisted -- they're just a
            // validation tool for Andre, not real backup history.
            return response()->json(['status' => 'ignored_dry_run']);
        }

        $stored = 0;
        $skipped = [];

        foreach ($data['results'] as $result) {
            $server = Server::where('name', $result['name'])->first();

            if (! $server) {
                $skipped[] = $result['name'];
                Log::warning("Agent '{$agent->slug}' reported a run for unknown server '{$result['name']}'");
                continue;
            }

            BackupRun::create([
                'server_id' => $server->id,
                'agent_id' => $agent->id,
                'status' => $result['success'] ? BackupStatus::Success : BackupStatus::Failed,
                'error' => $result['error'] ?? null,
                'started_at' => $result['started_at'] ?? null,
                'finished_at' => $result['finished_at'] ?? null,
            ]);
            $stored++;
        }

        foreach (($data['merge_errors'] ?? []) as $mergeError) {
            Log::warning("Agent '{$agent->slug}' config merge issue: {$mergeError}");
        }

        $agent->markOnline();

        return response()->json(['status' => 'ok', 'stored' => $stored, 'skipped' => $skipped]);
    }

    /**
     * POST /api/agent/heartbeat
     *
     * Marks the agent online and records when it last checked in. Purely
     * informational (drives the "Online/Offline" badge in the admin
     * panel) -- never gates whether the Pi is allowed to back things up.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $agent = $this->authenticatedAgent($request);

        $request->validate([
            'checked_in_at' => ['sometimes', 'date', 'nullable'],
            'config_fetch_ok' => ['sometimes', 'boolean', 'nullable'],
            'backup_exit_code' => ['sometimes', 'integer', 'nullable'],
        ]);

        $agent->markOnline();

        return response()->json(['status' => 'ok']);
    }
}
