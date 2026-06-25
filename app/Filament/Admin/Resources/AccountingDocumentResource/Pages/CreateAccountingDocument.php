<?php

namespace App\Filament\Admin\Resources\AccountingDocumentResource\Pages;

use App\Filament\Admin\Resources\AccountingDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccountingDocument extends CreateRecord
{
    protected static string $resource = AccountingDocumentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
