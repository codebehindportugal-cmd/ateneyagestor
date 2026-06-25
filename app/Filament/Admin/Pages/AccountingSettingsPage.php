<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class AccountingSettingsPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Acesso Contabilista';
    protected static ?string $navigationGroup = 'Contabilidade';
    protected static ?int    $navigationSort  = 2;
    protected static string  $view            = 'filament.pages.accounting-settings';

    public function getTitle(): string
    {
        return 'Acesso do Contabilista';
    }

    public function getViewData(): array
    {
        $token = Setting::get('accountant_token');

        return [
            'token'     => $token,
            'accessUrl' => $token ? url("/contabilista/{$token}") : null,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Gerar novo token')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Gerar novo token de acesso')
                ->modalDescription('O token anterior ficará inválido imediatamente. O contabilista precisará do novo URL.')
                ->action(function () {
                    Setting::set('accountant_token', Str::random(40));
                    Notification::make()
                        ->title('Novo token gerado')
                        ->body('Partilha o novo URL com o contabilista.')
                        ->success()
                        ->send();
                }),

            Action::make('revoke')
                ->label('Revogar acesso')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Revogar acesso do contabilista')
                ->modalDescription('O contabilista deixará de conseguir aceder imediatamente.')
                ->visible(fn () => filled(Setting::get('accountant_token')))
                ->action(function () {
                    Setting::set('accountant_token', null);
                    Notification::make()
                        ->title('Acesso revogado')
                        ->success()
                        ->send();
                }),
        ];
    }
}
