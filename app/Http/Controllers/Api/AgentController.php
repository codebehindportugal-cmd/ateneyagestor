<?php

namespace App\Http\Controllers\Api;

use App\Enums\BackupStatus;
use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\BackupRun;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Backs the /api/agent/* routes called by agent_sync.py on the agent.
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
     * Devolve as máquinas deste agente, cada uma com os sites a copiar lá
     * dentro (metadados apenas -- nunca segredos). O agente abre uma ligação
     * SSH por máquina e percorre os sites.
     */
    public function config(Request $request): JsonResponse
    {
        $agent = $this->authenticatedAgent($request);

        $servers = Server::query()
            ->with(['activeSites' => fn ($query) => $query->orderBy('name')])
            ->where('is_active', true)
            ->where(function ($query) use ($agent) {
                $query->whereNull('agent_id')->orWhere('agent_id', $agent->id);
            })
            ->orderBy('name')
            ->get()
            // Uma máquina sem sites ativos não tem nada a fazer -- não vale a
            // pena o agente abrir SSH para ela.
            ->filter(fn (Server $server) => $server->activeSites->isNotEmpty());

        return response()->json([
            'global'  => $agent->toAgentGlobalArray(),
            'servers' => $servers->map(fn (Server $server) => $server->toAgentArray())->values(),
        ]);
    }

    /**
     * POST /api/agent/runs
     *
     * Body: { results: [{name, type, success, error, started_at, finished_at,
     *                    size_bytes, nas_path, file_count}],
     *         merge_errors: [string], dry_run: bool }
     *
     * `name` é o nome do SITE. Registos antigos do agente mandavam o nome do
     * servidor; se não houver site com esse nome, tenta-se o servidor, para
     * um agente desatualizado não perder o relatório.
     */
    public function storeRunResults(Request $request): JsonResponse
    {
        $agent = $this->authenticatedAgent($request);

        $data = $request->validate([
            'dry_run'                => ['sometimes', 'boolean'],
            'results'                => ['required', 'array'],
            'results.*.name'         => ['required', 'string'],
            'results.*.type'         => ['sometimes', 'string', 'nullable'],
            'results.*.success'      => ['required', 'boolean'],
            'results.*.error'        => ['sometimes', 'string', 'nullable'],
            'results.*.started_at'   => ['sometimes', 'date', 'nullable'],
            'results.*.finished_at'  => ['sometimes', 'date', 'nullable'],
            'results.*.size_bytes'   => ['sometimes', 'integer', 'nullable'],
            'results.*.file_count'   => ['sometimes', 'integer', 'nullable'],
            'results.*.nas_path'     => ['sometimes', 'string', 'nullable'],
            'merge_errors'           => ['sometimes', 'array'],
        ]);

        if ($data['dry_run'] ?? false) {
            // Dry runs are intentionally never persisted -- they're just a
            // validation tool for Andre, not real backup history.
            return response()->json(['status' => 'ignored_dry_run']);
        }

        $stored = 0;
        $skipped = [];

        foreach ($data['results'] as $result) {
            $site = Site::where('name', $result['name'])->first();
            $server = $site?->server ?: Server::where('name', $result['name'])->first();

            if (! $site && ! $server) {
                $skipped[] = $result['name'];
                Log::warning("Agent '{$agent->slug}' reported a run for unknown site '{$result['name']}'");
                continue;
            }

            BackupRun::create([
                'server_id'   => $server?->id,
                'site_id'     => $site?->id,
                'agent_id'    => $agent->id,
                'status'      => $result['success'] ? BackupStatus::Success : BackupStatus::Failed,
                'error'       => $result['error'] ?? null,
                'started_at'  => $result['started_at'] ?? null,
                'finished_at' => $result['finished_at'] ?? null,
                'size_bytes'  => $result['size_bytes'] ?? null,
                'file_count'  => $result['file_count'] ?? 0,
                'nas_path'    => $result['nas_path'] ?? null,
            ]);
            $stored++;
        }

        foreach (($data['merge_errors'] ?? []) as $mergeError) {
            Log::warning("Agent '{$agent->slug}' config merge issue: {$mergeError}");
        }

        // Contagens da corrida, para o painel poder dizer "12 de 14 falharam"
        // sem ter de percorrer o histórico de BackupRun a cada visita.
        $total  = count($data['results']);
        $failed = collect($data['results'])->reject(fn (array $r) => (bool) $r['success'])->count();

        $agent->forceFill([
            'last_backup_at'     => now(),
            'last_backup_total'  => $total,
            'last_backup_failed' => $failed,
        ])->save();

        if ($failed > 0) {
            Log::warning("Agent '{$agent->slug}' reportou {$failed} de {$total} backups falhados");
        }

        $agent->markOnline();

        return response()->json([
            'status'  => 'ok',
            'stored'  => $stored,
            'skipped' => $skipped,
            'failed'  => $failed,
            'total'   => $total,
        ]);
    }

    /**
     * POST /api/agent/heartbeat
     *
     * Marks the agent online and records when it last checked in. Purely
     * informational (drives the "Online/Offline" badge in the admin
     * panel) -- never gates whether the agent is allowed to back things up.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $agent = $this->authenticatedAgent($request);

        $data = $request->validate([
            'checked_in_at'    => ['sometimes', 'date', 'nullable'],
            'config_fetch_ok'  => ['sometimes', 'boolean', 'nullable'],
            'backup_exit_code' => ['sometimes', 'integer', 'nullable'],
        ]);

        // Até 27/08/2026 o exit code era validado e descartado: o agente
        // reportava 12 falhas em 14 e o painel mostrava o agente "online" e
        // mais nada. Guardá-lo é o que torna a falha visível.
        if (array_key_exists('backup_exit_code', $data) && $data['backup_exit_code'] !== null) {
            $agent->forceFill([
                'last_backup_exit_code' => (int) $data['backup_exit_code'],
                'last_backup_at'        => now(),
            ])->save();

            if ((int) $data['backup_exit_code'] !== 0) {
                Log::warning(
                    "Agent '{$agent->slug}' terminou a corrida de backups com exit code {$data['backup_exit_code']}"
                );
            }
        }

        $agent->markOnline();

        return response()->json(['status' => 'ok']);
    }
}
