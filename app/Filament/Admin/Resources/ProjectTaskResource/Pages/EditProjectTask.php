<?php

namespace App\Filament\Admin\Resources\ProjectTaskResource\Pages;

use App\Filament\Admin\Resources\ProjectTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProjectTask extends EditRecord
{
    protected static string $resource = ProjectTaskResource::class;

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
