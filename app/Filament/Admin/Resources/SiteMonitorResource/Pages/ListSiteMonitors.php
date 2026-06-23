<?php

namespace App\Filament\Admin\Resources\SiteMonitorResource\Pages;

use App\Filament\Admin\Resources\SiteMonitorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSiteMonitors extends ListRecords
{
    protected static string $resource = SiteMonitorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
