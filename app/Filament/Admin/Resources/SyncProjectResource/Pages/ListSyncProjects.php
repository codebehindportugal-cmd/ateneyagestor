<?php

namespace App\Filament\Admin\Resources\SyncProjectResource\Pages;

use App\Filament\Admin\Resources\SyncProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSyncProjects extends ListRecords
{
    protected static string $resource = SyncProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
