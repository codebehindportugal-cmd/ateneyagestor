<?php

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_accountant_token')
                ->label(fn () => $this->record->accountant_token ? 'Novo token contabilista' : 'Gerar acesso contabilista')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Gerar token de acesso para o contabilista')
                ->modalDescription(fn () => $this->record->accountant_token
                    ? 'O token anterior ficará inválido imediatamente. O contabilista precisará do novo URL.'
                    : 'Cria um URL de acesso só de leitura para o contabilista deste cliente.'
                )
                ->action(function () {
                    $this->record->update(['accountant_token' => Str::random(40)]);

                    $url = url("/contabilista/cliente/{$this->record->accountant_token}");

                    Notification::make()
                        ->title('Token gerado')
                        ->body("URL: {$url}")
                        ->success()
                        ->persistent()
                        ->send();
                }),

            Actions\Action::make('revoke_accountant_token')
                ->label('Revogar acesso contabilista')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Revogar acesso do contabilista')
                ->modalDescription('O contabilista perderá acesso imediatamente.')
                ->visible(fn () => filled($this->record->accountant_token))
                ->action(function () {
                    $this->record->update(['accountant_token' => null]);

                    Notification::make()->title('Acesso revogado')->success()->send();
                }),

            Actions\Action::make('copy_accountant_url')
                ->label('Copiar URL contabilista')
                ->icon('heroicon-o-clipboard-document')
                ->color('gray')
                ->visible(fn () => filled($this->record->accountant_token))
                ->action(function () {
                    $url = url("/contabilista/cliente/{$this->record->accountant_token}");
                    Notification::make()
                        ->title('URL do contabilista')
                        ->body($url)
                        ->info()
                        ->persistent()
                        ->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
