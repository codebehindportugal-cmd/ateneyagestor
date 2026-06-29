<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\ProductivityEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductivityController extends Controller
{
    private function authenticatedAgent(Request $request): Agent
    {
        $tokenable = $request->user();

        abort_unless($tokenable instanceof Agent, 403, 'Token nao pertence a um agente autorizado.');

        return $tokenable;
    }

    public function config(Request $request): JsonResponse
    {
        $agent = $this->authenticatedAgent($request);

        abort_unless($agent->productivity_monitor_enabled, 403, 'Monitor de produtividade desativado para este agente.');

        $agent->markOnline();

        return response()->json([
            'status' => 'ok',
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'computer_name' => $agent->computer_name,
                'assigned_user_name' => $agent->assigned_user_name,
                'department' => $agent->department,
                'asset_tag' => $agent->asset_tag,
            ],
            'policy' => [
                'collect_apps' => true,
                'collect_domains' => (bool) $agent->productivity_collect_domains,
                'collect_window_titles' => false,
                'collect_screenshots' => false,
                'collect_keystrokes' => false,
                'idle_threshold_seconds' => (int) $agent->productivity_idle_threshold_seconds,
                'send_interval_seconds' => (int) $agent->productivity_send_interval_seconds,
                'sample_interval_seconds' => (int) $agent->productivity_sample_interval_seconds,
                'work_hours' => [
                    'enabled' => (bool) $agent->productivity_work_hours_enabled,
                    'start' => $agent->productivity_work_start ?: '09:00',
                    'end' => $agent->productivity_work_end ?: '18:00',
                    'weekdays' => array_map('intval', $agent->productivity_work_weekdays ?: [1, 2, 3, 4, 5]),
                ],
            ],
        ]);
    }

    public function storeEvents(Request $request): JsonResponse
    {
        $agent = $this->authenticatedAgent($request);

        abort_unless($agent->productivity_monitor_enabled, 403, 'Monitor de produtividade desativado para este agente.');

        $data = $request->validate([
            'device_uid' => ['required', 'string', 'max:120'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'events' => ['required', 'array', 'max:500'],
            'events.*.event_type' => ['required', 'string', 'in:app,site,active,idle,heartbeat'],
            'events.*.app_name' => ['nullable', 'string', 'max:255'],
            'events.*.process_name' => ['nullable', 'string', 'max:255'],
            'events.*.domain' => ['nullable', 'string', 'max:255'],
            'events.*.activity_state' => ['nullable', 'string', 'max:20'],
            'events.*.started_at' => ['required', 'date'],
            'events.*.ended_at' => ['nullable', 'date'],
            'events.*.duration_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'events.*.metadata' => ['nullable', 'array'],
        ]);

        $stored = 0;

        foreach ($data['events'] as $event) {
            ProductivityEvent::create([
                'agent_id' => $agent->id,
                'device_uid' => $data['device_uid'],
                'hostname' => $data['hostname'] ?? null,
                'event_type' => $event['event_type'],
                'app_name' => $event['app_name'] ?? null,
                'process_name' => $event['process_name'] ?? null,
                'domain' => $event['domain'] ?? null,
                'activity_state' => $event['activity_state'] ?? null,
                'started_at' => $event['started_at'],
                'ended_at' => $event['ended_at'] ?? null,
                'duration_seconds' => $event['duration_seconds'],
                'metadata' => $event['metadata'] ?? null,
            ]);

            $stored++;
        }

        $agent->markOnline();

        return response()->json(['status' => 'ok', 'stored' => $stored]);
    }
}
