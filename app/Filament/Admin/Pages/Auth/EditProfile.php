<?php

namespace App\Filament\Admin\Pages\Auth;

use App\Support\TokenFaturas;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Support\HtmlString;

/**
 * O perfil de cada pessoa, com a chave da API de faturas.
 *
 * 29/09/2026: a chave so se gerava em Contabilidade > Acesso Contabilista ou
 * com `php artisan faturas:emitir-token`. Foi pedido um botao no perfil, que e
 * onde cada um vai buscar o que e seu. A regra continua a do TokenFaturas: so
 * administradores, so a ability faturas:write, e gerar uma nova revoga a
 * anterior. A chave mostra-se uma vez; a base de dados so guarda o hash.
 *
 * Pede a password actual antes de gerar: quem encontrar uma sessao aberta
 * nao deve conseguir tirar dali uma chave que continua a valer depois.
 */
class EditProfile extends BaseEditProfile
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getChaveApiSection(),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->inlineLabel(! static::isSimple()),
            ),
        ];
    }

    protected function getChaveApiSection(): Section
    {
        return Section::make('Chave da API')
            ->description('Serve para o chat registar faturas de fornecedor (/api/v1/faturas). Só dá para isso.')
            ->schema([
                Placeholder::make('estado_chave_api')
                    ->label('Estado')
                    ->content(fn (): HtmlString => $this->descricaoChave()),
            ])
            ->visible(fn (): bool => $this->getUser()->isAdmin());
    }

    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            $this->getGerarChaveAction(),
            $this->getRevogarChaveAction(),
        ];
    }

    protected function getGerarChaveAction(): Action
    {
        return Action::make('gerarChaveApi')
            ->label(fn (): string => $this->chaveActual() ? 'Gerar nova chave da API' : 'Gerar chave da API')
            ->icon('heroicon-o-key')
            ->color('gray')
            ->visible(fn (): bool => $this->getUser()->isAdmin())
            ->modalHeading('Gerar chave da API')
            ->modalDescription(fn (): string => $this->chaveActual()
                ? 'A chave actual deixa de funcionar assim que gerares a nova. A nova só é mostrada uma vez.'
                : 'A chave só é mostrada uma vez. Copia-a logo.')
            ->form([
                TextInput::make('password_actual')
                    ->label('Password actual')
                    ->password()
                    ->revealable()
                    ->required()
                    ->currentPassword(),
            ])
            ->modalSubmitActionLabel('Gerar')
            ->action(function (): void {
                ['token' => $token, 'revogados' => $revogados] = TokenFaturas::emitir($this->getUser());

                Notification::make()
                    ->title('A tua chave da API')
                    ->body(new HtmlString(
                        '<code style="word-break:break-all;user-select:all">' . e($token) . '</code><br><br>'
                        . ($revogados > 0 ? 'A chave anterior deixou de funcionar. ' : '')
                        . 'Copia-a agora: não volta a ser mostrada.'
                    ))
                    ->success()
                    ->persistent()
                    ->send();
            });
    }

    protected function getRevogarChaveAction(): Action
    {
        return Action::make('revogarChaveApi')
            ->label('Revogar chave')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (): bool => $this->getUser()->isAdmin() && $this->chaveActual() !== null)
            ->requiresConfirmation()
            ->modalHeading('Revogar a chave da API')
            ->modalDescription('O chat deixa de conseguir registar faturas até gerares uma nova.')
            ->action(function (): void {
                TokenFaturas::revogar($this->getUser());

                Notification::make()->title('Chave revogada')->success()->send();
            });
    }

    /** O token Sanctum da API de faturas deste utilizador, se existir. */
    protected function chaveActual(): ?object
    {
        return $this->getUser()->tokens()
            ->where('name', TokenFaturas::NOME_POR_OMISSAO)
            ->latest('id')
            ->first();
    }

    protected function descricaoChave(): HtmlString
    {
        $chave = $this->chaveActual();

        if (! $chave) {
            return new HtmlString('Sem chave. Usa o botão <strong>Gerar chave da API</strong> no fim da página.');
        }

        $criada = $chave->created_at?->format('d/m/Y H:i') ?? '?';
        $uso    = $chave->last_used_at ? $chave->last_used_at->format('d/m/Y H:i') : 'nunca usada';

        return new HtmlString(e("Activa desde {$criada} · último uso: {$uso}"));
    }
}
