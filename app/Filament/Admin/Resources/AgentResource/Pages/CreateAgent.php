<?php

namespace App\Filament\Admin\Resources\AgentResource\Pages;

use App\Filament\Admin\Resources\AgentResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAgent extends CreateRecord
{
    protected static string $resource = AgentResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function afterCreate(): void
    {
        if ($this->record->agent_type === 'productivity') {
            Notification::make()
                ->title('Computador criado')
                ->body('Confirma os dados e usa "Download instalador" para gerar o ZIP ja com URL e chave API preenchidas.')
                ->success()
                ->send();

            return;
        }

        $plainTextToken = $this->record->createToken('agent_sync')->plainTextToken;

        Notification::make()
            ->title('Agente criado -- token gerado')
            ->body("Copia este token agora, nao volta a ser mostrado:\n\n{$plainTextToken}\n\nCola-o em agent_config.yaml no Pi, no campo api.token.")
            ->success()
            ->persistent()
            ->send();
    }
}
