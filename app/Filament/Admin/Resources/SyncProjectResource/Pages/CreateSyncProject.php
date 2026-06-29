<?php

namespace App\Filament\Admin\Resources\SyncProjectResource\Pages;

use App\Filament\Admin\Resources\SyncProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSyncProject extends CreateRecord
{
    protected static string $resource = SyncProjectResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
