<?php

namespace App\Filament\Admin\Resources\AccountingDocumentResource\Pages;

use App\Filament\Admin\Resources\AccountingDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccountingDocument extends EditRecord
{
    protected static string $resource = AccountingDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
