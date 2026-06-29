<?php

use App\Models\Agent;
use App\Models\Setting;
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
if (Setting::bool('cron.site_monitor.enabled', true)) {
    Schedule::command('monitor:sites')
        ->cron(Setting::get('cron.site_monitor.cron', '*/5 * * * *'))
        ->name('monitor:sites');
}

// TCP connectivity check (SSH port) for all active servers.
if (Setting::bool('cron.server_check.enabled', true)) {
    Schedule::command('server:check')
        ->cron(Setting::get('cron.server_check.cron', '*/5 * * * *'))
        ->name('server:check');
}

// Prune uptime check history older than 30 days (288 checks/day per monitor).
Schedule::call(function () {
    \App\Models\SiteMonitorCheck::where('checked_at', '<', now()->subDays(30))->delete();
})->daily()->name('site-monitor-checks:prune');

// Backup of all active servers; cadence configured in Sistema > Agendamentos.
if (Setting::bool('cron.backup.enabled', true)) {
    Schedule::command('backup:run --all')
        ->cron(Setting::get('cron.backup.cron', '0 3 * * *'))
        ->name('backup:run-all')
        ->withoutOverlapping(120);
}

// Security scan of all active servers; cadence configured in Sistema > Agendamentos.
if (Setting::bool('cron.security_scan.enabled', true)) {
    Schedule::command('security:scan --all')
        ->cron(Setting::get('cron.security_scan.cron', '0 4 * * 1'))
        ->name('security:scan-all')
        ->withoutOverlapping(120);
}

// Composer update visibility. This only reports available updates; it does not install them.
if (Setting::bool('cron.updates.enabled', false)) {
    Schedule::exec('composer outdated --direct')
        ->cron(Setting::get('cron.updates.cron', '0 5 * * 1'))
        ->name('updates:composer-check');
}
// Dynamic scheduling: each active SyncProject with a runner gets its own schedule.
// Wrapped in try/catch so a missing DB table during migrations doesn't break boot.
try {
    SyncProject::where('is_active', true)
        ->where('runner_mode', 'local')
        ->whereNotNull('runner_script_path')
        ->whereNotNull('runner_schedule')
        ->get()
        ->each(function (SyncProject $p) {
            Schedule::command("sync:run {$p->slug}")
                ->cron($p->runner_schedule)
                ->name("sync:{$p->slug}")
                ->withoutOverlapping(240); // 4h - syncs can run for 2h on large catalogues
        });
} catch (\Throwable) {
    // DB not ready yet (e.g. first deploy before migrations run).
}
