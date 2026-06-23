<?php

namespace App\Filament\Admin\Resources\ServerResource\Pages;

use App\Filament\Admin\Resources\ServerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServer extends EditRecord
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
