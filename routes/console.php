<?php

use App\Models\Agent;
use App\Models\SyncProject;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Marks an agent (the Pi) as "offline" in the admin panel if it hasn't
// checked in for a while -- purely informational, does not touch backups.
// Tune the threshold to comfortably exceed your cron interval (e.g. if
// the Pi runs hourly, 3h gives margin for a slow/retried run).
Schedule::call(function () {
    Agent::where('last_seen_at', '<', now()->subHours(3))
        ->where('status', 'online')
        ->update(['status' => 'offline']);
})->everyFifteenMinutes()->name('agents:mark-stale-offline');

// Flags invoices that are issued, unpaid, and past their due date.
Schedule::call(function () {
    \App\Models\Invoice::where('status', 'issued')
        ->whereNull('paid_at')
        ->where('due_at', '<', now())
        ->update(['status' => 'overdue']);
})->daily()->name('invoices:mark-overdue');

// HTTP uptime checks for all active site monitors.
Schedule::command('monitor:sites')->everyFiveMinutes()->name('monitor:sites');

// TCP connectivity check (SSH port) for all active servers.
Schedule::command('server:check')->everyFiveMinutes()->name('server:check');

// Prune uptime check history older than 30 days (288 checks/day per monitor).
Schedule::call(function () {
    \App\Models\SiteMonitorCheck::where('checked_at', '<', now()->subDays(30))->delete();
})->daily()->name('site-monitor-checks:prune');

// Daily backup of all active servers at 03:00. withoutOverlapping(120) ensures a long
// run never causes a double execution if the cron fires again before it finishes.
Schedule::command('backup:run --all')
    ->dailyAt('03:00')
    ->name('backup:run-all')
    ->withoutOverlapping(120);

// Weekly security scan of all active servers (every Monday at 04:00).
Schedule::command('security:scan --all')
    ->weeklyOn(1, '04:00')
    ->name('security:scan-all')
    ->withoutOverlapping(120);

// Dynamic scheduling: each active SyncProject with a runner gets its own schedule.
// Wrapped in try/catch so a missing DB table during migrations doesn't break boot.
try {
    SyncProject::where('is_active', true)
        ->whereNotNull('runner_script_path')
        ->whereNotNull('runner_schedule')
        ->get()
        ->each(function (SyncProject $p) {
            Schedule::command("sync:run {$p->slug}")
                ->cron($p->runner_schedule)
                ->name("sync:{$p->slug}")
                ->withoutOverlapping(120);
        });
} catch (\Throwable) {
    // DB not ready yet (e.g. first deploy before migrations run).
}
