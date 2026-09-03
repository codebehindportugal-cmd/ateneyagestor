<?php

namespace App\Filament\Admin\Resources\ProjectTaskResource\Pages;

use App\Filament\Admin\Resources\ProjectTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProjectTask extends CreateRecord
{
    protected static string $resource = ProjectTaskResource::class;

    public function getTitle(): string
    {
        return 'Nova tarefa';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
