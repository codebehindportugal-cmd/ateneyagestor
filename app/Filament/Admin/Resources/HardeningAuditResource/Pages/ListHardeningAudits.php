<?php

namespace App\Filament\Admin\Resources\HardeningAuditResource\Pages;

use App\Filament\Admin\Resources\HardeningAuditResource;
use App\Models\Server;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListHardeningAudits extends ListRecords
{
    protected static string $resource = HardeningAuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('auditar')
                ->label('Auditar servidor')
                ->icon('heroicon-m-play')
                ->form([
                    Forms\Components\Select::make('server_id')
                        ->label('Servidor')
                        ->options(fn () => Server::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                ])
                ->modalDescription('Só lê — nada é alterado no servidor. Demora cerca de um minuto por máquina.')
                ->modalSubmitActionLabel('Auditar')
                ->action(function (array $data) {
                    $auditoria = HardeningAuditResource::auditar(Server::findOrFail($data['server_id']));

                    if ($auditoria->estado !== 'erro') {
                        $this->redirect(HardeningAuditResource::getUrl('view', ['record' => $auditoria]));
                    }
                }),

            Actions\Action::make('auditar_todos')
                ->label('Auditar todos')
                ->icon('heroicon-m-queue-list')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Auditar todos os servidores activos')
                ->modalDescription('Continua a ser só leitura, mas são vários minutos. Se o browser desistir pelo caminho, corre antes: php artisan seguranca:auditar --todos')
                ->action(function () {
                    foreach (Server::where('is_active', true)->orderBy('name')->get() as $servidor) {
                        HardeningAuditResource::auditar($servidor);
                    }
                }),
        ];
    }
}
