<?php

namespace App\Console\Commands;

use App\Enums\MonitorStatus;
use App\Models\SiteMonitor;
use App\Models\SiteMonitorCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckSiteMonitors extends Command
{
    protected $signature = 'monitor:sites {--id= : Check only this monitor ID}';

    protected $description = 'Check HTTP status of all active site monitors';

    public function handle(): int
    {
        $query = SiteMonitor::query()->where('is_active', true);

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $monitors = $query->get();

        if ($monitors->isEmpty()) {
            $this->info('Nenhum monitor ativo.');
            return 0;
        }

        $this->info("A verificar {$monitors->count()} site(s)...");

        foreach ($monitors as $monitor) {
            $this->checkMonitor($monitor);
        }

        return 0;
    }

    private function checkMonitor(SiteMonitor $monitor): void
    {
        $wasDown = $monitor->status === MonitorStatus::Down;
        $start   = microtime(true);

        try {
            $response = Http::timeout(15)
                ->withoutVerifying() // don't fail on self-signed SSL
                ->get($monitor->url);

            $ms     = (int) ((microtime(true) - $start) * 1000);
            $isUp   = $response->successful() || $response->redirect();
            $status = $isUp ? MonitorStatus::Up : MonitorStatus::Down;

            $error = $isUp ? null : "HTTP {$response->status()}";

            $monitor->update([
                'status'           => $status,
                'last_http_code'   => $response->status(),
                'last_response_ms' => $ms,
                'last_error'       => $error,
                'last_checked_at'  => now(),
                'went_down_at'     => ($status === MonitorStatus::Down && ! $wasDown) ? now() : $monitor->went_down_at,
            ]);

            SiteMonitorCheck::create([
                'site_monitor_id' => $monitor->id,
                'status'          => $status->value,
                'http_code'       => $response->status(),
                'response_ms'     => $ms,
                'error'           => $error,
                'checked_at'      => now(),
            ]);

            $icon = $isUp ? '✓' : '✗';
            $this->line(" {$icon} {$monitor->name} — HTTP {$response->status()} ({$ms}ms)");
        } catch (\Exception $e) {
            $ms    = (int) ((microtime(true) - $start) * 1000);
            $error = substr($e->getMessage(), 0, 500);

            $monitor->update([
                'status'           => MonitorStatus::Down,
                'last_error'       => $error,
                'last_response_ms' => $ms,
                'last_checked_at'  => now(),
                'went_down_at'     => $wasDown ? $monitor->went_down_at : now(),
            ]);

            SiteMonitorCheck::create([
                'site_monitor_id' => $monitor->id,
                'status'          => MonitorStatus::Down->value,
                'response_ms'     => $ms,
                'error'           => $error,
                'checked_at'      => now(),
            ]);

            $this->line(" ✗ {$monitor->name} — {$e->getMessage()}");
        }
    }
}
