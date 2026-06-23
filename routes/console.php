<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Agent;

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
