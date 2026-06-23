<?php

namespace App\Filament\Admin\Resources\SiteMonitorResource\Pages;

use App\Filament\Admin\Resources\SiteMonitorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSiteMonitor extends EditRecord
{
    protected static string $resource = SiteMonitorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
