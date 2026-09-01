<?php

namespace App\Filament\Admin\Resources\SiteUpdateResource\Pages;

use App\Filament\Admin\Resources\SiteUpdateResource;
use Filament\Resources\Pages\ListRecords;

class ListSiteUpdates extends ListRecords
{
    protected static string $resource = SiteUpdateResource::class;

    /** Enquanto houver trabalho na fila, a pagina refresca-se sozinha. */
    public function getPollingInterval(): ?string
    {
        return \App\Models\SiteUpdate::whereIn('status', ['queued', 'running'])->exists() ? '10s' : null;
    }
}
