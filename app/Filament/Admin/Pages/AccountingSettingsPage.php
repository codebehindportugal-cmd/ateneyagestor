<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use App\Support\TokenFaturas;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class AccountingSettingsPage extends Page
{
    /** Só o administrador. Um estagiário nem vê isto no menu. */
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() === true;
    }

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
            // O token da API de faturas vive aqui porque e o outro acesso
            // de fora a contabilidade. Mostra-se uma vez, na notificacao: a
            // base de dados so guarda o hash.
            Action::make('tokenFaturas')
                ->label('Token da API de faturas')
                ->icon('heroicon-o-command-line')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Gerar token da API de faturas')
                ->modalDescription('O token só serve para registar faturas de fornecedor (/api/v1/faturas). Gerar um novo revoga o anterior — a skill do chat passa a precisar do novo. Copia-o já: não volta a ser mostrado.')
                ->modalSubmitActionLabel('Gerar')
                ->action(function () {
                    ['token' => $token, 'revogados' => $revogados] = TokenFaturas::emitir(auth()->user());

                    Notification::make()
                        ->title('Token da API de faturas')
                        ->body("<code>".e($token)."</code><br><br>"
                            .($revogados > 0 ? 'O token anterior foi revogado. ' : '')
                            .'Copia-o agora — não volta a ser mostrado.')
                        ->success()
                        ->persistent()
                        ->send();
                }),

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
