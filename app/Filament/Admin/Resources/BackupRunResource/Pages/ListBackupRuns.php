<?php

namespace App\Filament\Admin\Resources\BackupRunResource\Pages;

use App\Filament\Admin\Resources\BackupRunResource;
use Filament\Resources\Pages\ListRecords;

class ListBackupRuns extends ListRecords
{
    protected static string $resource = BackupRunResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
