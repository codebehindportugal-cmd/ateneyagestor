<?php

namespace App\Filament\Admin\Resources\CredentialResource\Pages;

use App\Filament\Admin\Resources\CredentialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCredentials extends ListRecords
{
    protected static string $resource = CredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
