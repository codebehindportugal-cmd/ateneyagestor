<?php

namespace App\Providers;

use App\Policies\AdminOnlyPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Tudo o que é da casa e não se mostra a um estagiário. O Laravel descobre
     * sozinho as políticas com nome de modelo (ProjectPolicy, TicketPolicy…);
     * estes modelos partilham todos a mesma regra, por isso apontam de uma vez
     * para a AdminOnlyPolicy.
     *
     * Modelo novo que seja interno: acrescenta-o aqui.
     */
    private const ADMIN_ONLY_MODELS = [
        \App\Models\AccountingDocument::class,
        \App\Models\Agent::class,
        \App\Models\BackupRun::class,
        \App\Models\Brand::class,
        \App\Models\Client::class,
        \App\Models\ClientDocument::class,
        \App\Models\Credential::class,
        \App\Models\Invoice::class,
        \App\Models\ProductivityEvent::class,
        \App\Models\Routine::class,
        \App\Models\RoutineOccurrence::class,
        \App\Models\SecurityScan::class,
        \App\Models\Server::class,
        \App\Models\Service::class,
        \App\Models\Setting::class,
        \App\Models\Site::class,
        \App\Models\SiteMonitor::class,
        \App\Models\SiteMonitorCheck::class,
        \App\Models\SiteUpdate::class,
        \App\Models\SupplierInvoice::class,
        \App\Models\SupplierInvoiceItem::class,
        \App\Models\SyncProject::class,
        \App\Models\SyncRun::class,
        \App\Models\ClaudeRun::class,
        \App\Models\User::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Behind a reverse proxy (typical on Plesk/VPS hosting), make sure
        // generated URLs use https when the app itself is served over it.
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        foreach (self::ADMIN_ONLY_MODELS as $model) {
            Gate::policy($model, AdminOnlyPolicy::class);
        }
    }
}
