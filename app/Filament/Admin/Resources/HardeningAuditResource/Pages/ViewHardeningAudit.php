<?php

namespace App\Filament\Admin\Resources\HardeningAuditResource\Pages;

use App\Filament\Admin\Resources\HardeningAuditResource;
use App\Jobs\CorrigirEndurecimento;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewHardeningAudit extends ViewRecord
{
    protected static string $resource = HardeningAuditResource::class;

    protected static string $view = 'filament.admin.resources.hardening-audit.ver';

    public function getTitle(): string
    {
        return 'Endurecimento — '.($this->record->server?->name ?? '');
    }

    /** Enquanto a auditoria ou uma correcção corre na fila, a página vai-se refrescando. */
    public function refrescar(): void
    {
        $this->record->refresh();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reauditar')
                ->label('Auditar outra vez')
                ->icon('heroicon-m-arrow-path')
                ->disabled(fn () => $this->record->estado === 'pendente')
                ->action(function () {
                    $nova = HardeningAuditResource::auditar($this->record->server);
                    $this->redirect(HardeningAuditResource::getUrl('view', ['record' => $nova]));
                }),
        ];
    }

    /**
     * O botão que corre a correcção de uma verificação.
     *
     * Mostra sempre o comando exacto antes de o correr, e o aviso quando a
     * correcção tem lado mau (fechar o SSH por senha, reiniciar o MySQL…).
     * A seguir volta a verificar só aquela linha e actualiza-a.
     */
    public function corrigirAction(): Actions\Action
    {
        return Actions\Action::make('corrigir')
            ->label('Corrigir')
            ->icon('heroicon-m-wrench-screwdriver')
            ->color('warning')
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments) => 'Corrigir: '.($this->record->resultado($arguments['chave'])['label'] ?? ''))
            ->modalDescription(function (array $arguments) {
                $r = $this->record->resultado($arguments['chave']) ?? [];

                $html = '';

                if (filled($r['perigo'] ?? null)) {
                    $html .= '<p style="color: rgb(180 83 9); margin-bottom: .75rem;"><strong>Atenção:</strong> '
                        .e($r['perigo']).'</p>';
                }

                $html .= '<p class="text-sm" style="margin-bottom: .35rem;">Vai correr no servidor:</p>';
                $html .= '<pre style="white-space: pre-wrap; word-break: break-all; font-size: .75rem; background: rgb(248 250 252); padding: .6rem; border-radius: .375rem; max-height: 14rem; overflow: auto;">'
                    .e($r['correcao'] ?? '').'</pre>';

                return new HtmlString($html);
            })
            ->modalSubmitActionLabel('Correr no servidor')
            ->action(function (array $arguments) {
                $chave = $arguments['chave'];
                $linha = $this->record->resultado($chave) ?? [];

                // Marca a linha como "a corrigir" e manda para a fila; a página
                // actualiza-se sozinha e mostra a saída quando acabar.
                $this->record->substituirResultado($chave, ['a_correr' => true] + $linha);
                CorrigirEndurecimento::dispatch($this->record->fresh(), $chave);
                $this->record->refresh();

                Notification::make()
                    ->title('A corrigir em segundo plano')
                    ->body('A linha actualiza sozinha quando acabar, com a saída completa do servidor.')
                    ->info()
                    ->send();
            });
    }
}
