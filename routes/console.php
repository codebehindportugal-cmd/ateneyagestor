<?php

use App\Models\Agent;
use App\Models\Setting;
use App\Models\SyncProject;
use Cron\CronExpression;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$safeCron = static function (string $key, string $default): string {
    $value = (string) Setting::get($key, $default);

    return CronExpression::isValidExpression($value) ? $value : $default;
};

// Marks an agent (the Pi) as offline in the admin panel if it has not checked in.
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

if (Setting::bool('cron.site_monitor.enabled', true)) {
    Schedule::command('monitor:sites')
        ->cron($safeCron('cron.site_monitor.cron', '*/5 * * * *'))
        ->name('monitor:sites');
}

if (Setting::bool('cron.server_check.enabled', true)) {
    Schedule::command('server:check')
        ->cron($safeCron('cron.server_check.cron', '*/5 * * * *'))
        ->name('server:check');
}

Schedule::call(function () {
    \App\Models\SiteMonitorCheck::where('checked_at', '<', now()->subDays(30))->delete();
})->daily()->name('site-monitor-checks:prune');

if (Setting::bool('cron.backup.enabled', true)) {
    Schedule::command('backup:run --all')
        ->cron($safeCron('cron.backup.cron', '0 3 * * *'))
        ->name('backup:run-all')
        ->withoutOverlapping(120);
}

if (Setting::bool('cron.security_scan.enabled', true)) {
    Schedule::command('security:scan --all')
        ->cron($safeCron('cron.security_scan.cron', '0 4 * * 1'))
        ->name('security:scan-all')
        ->withoutOverlapping(120);
}

if (Setting::bool('cron.updates.enabled', false)) {
    Schedule::exec('composer outdated --direct 2>&1')
        ->cron($safeCron('cron.updates.cron', '0 5 * * 1'))
        ->sendOutputTo(storage_path('logs/update-check.log'))
        ->name('updates:composer-check');
}

try {
    SyncProject::where('is_active', true)
        ->where('runner_mode', 'local')
        ->whereNotNull('runner_script_path')
        ->whereNotNull('runner_schedule')
        ->get()
        ->each(function (SyncProject $project) {
            if (! CronExpression::isValidExpression((string) $project->runner_schedule)) {
                return;
            }

            Schedule::command("sync:run {$project->slug}")
                ->cron($project->runner_schedule)
                ->name("sync:{$project->slug}")
                ->withoutOverlapping(240);
        });
} catch (\Throwable) {
    // DB not ready yet, for example during first deploy before migrations run.
}
