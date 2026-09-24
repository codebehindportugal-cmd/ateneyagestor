<?php

namespace App\Filament\Admin\Resources\SpeedAuditResource\Pages;

use App\Filament\Admin\Resources\SpeedAuditResource;
use App\Models\Server;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListSpeedAudits extends ListRecords
{
    protected static string $resource = SpeedAuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('analisar')
                ->label('Analisar servidor')
                ->icon('heroicon-m-play')
                ->form([
                    Forms\Components\Select::make('server_id')
                        ->label('Servidor')
                        ->options(fn () => Server::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                ])
                ->modalDescription('Só lê — nada é alterado. Corre em segundo plano (1–3 minutos, conforme o número de sites); a página actualiza sozinha.')
                ->modalSubmitActionLabel('Analisar')
                ->action(function (array $data) {
                    $auditoria = SpeedAuditResource::lancar(Server::findOrFail($data['server_id']));
                    $this->redirect(SpeedAuditResource::getUrl('view', ['record' => $auditoria]));
                }),
        ];
    }
}
