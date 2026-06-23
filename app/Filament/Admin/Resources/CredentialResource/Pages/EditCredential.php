<?php

namespace App\Filament\Admin\Resources\CredentialResource\Pages;

use App\Filament\Admin\Resources\CredentialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCredential extends EditRecord
{
    protected static string $resource = CredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
