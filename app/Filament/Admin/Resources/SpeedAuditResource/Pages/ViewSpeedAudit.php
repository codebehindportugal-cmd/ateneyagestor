<?php

namespace App\Filament\Admin\Resources\SpeedAuditResource\Pages;

use App\Filament\Admin\Resources\SpeedAuditResource;
use App\Jobs\CorrigirVelocidade;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewSpeedAudit extends ViewRecord
{
    protected static string $resource = SpeedAuditResource::class;

    protected static string $view = 'filament.admin.resources.speed-audit.ver';

    public function getTitle(): string
    {
        return 'Velocidade — ' . ($this->record->server?->name ?? '');
    }

    /** Enquanto a auditoria corre na fila, a página vai-se refrescando. */
    public function refrescar(): void
    {
        $this->record->refresh();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reanalisar')
                ->label('Analisar outra vez')
                ->icon('heroicon-m-arrow-path')
                ->disabled(fn () => $this->record->estado === 'pendente')
                ->action(function () {
                    $nova = SpeedAuditResource::lancar($this->record->server);
                    $this->redirect(SpeedAuditResource::getUrl('view', ['record' => $nova]));
                }),
        ];
    }

    /**
     * Botão que corre a correcção de uma verificação — mostra sempre o
     * comando exacto e o aviso antes, e a seguir volta a verificar essa linha.
     */
    public function corrigirAction(): Actions\Action
    {
        return Actions\Action::make('corrigir')
            ->label('Corrigir')
            ->icon('heroicon-m-wrench-screwdriver')
            ->color('warning')
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading(function (array $arguments) {
                $r = $this->record->resultado($arguments['chave']) ?? [];

                return 'Corrigir: ' . ($r['label'] ?? '') . (($r['grupo'] ?? 'Máquina') !== 'Máquina' ? ' — ' . $r['grupo'] : '');
            })
            ->modalDescription(function (array $arguments) {
                $r = $this->record->resultado($arguments['chave']) ?? [];
                $html = '';

                if (filled($r['perigo'] ?? null)) {
                    $html .= '<p style="color: rgb(180 83 9); margin-bottom: .75rem;"><strong>Atenção:</strong> ' . e($r['perigo']) . '</p>';
                }

                $html .= '<p class="text-sm" style="margin-bottom: .35rem;">Vai correr no servidor:</p>';
                $html .= '<pre style="white-space: pre-wrap; word-break: break-all; font-size: .72rem; background: rgb(248 250 252); padding: .6rem; border-radius: .375rem; max-height: 16rem; overflow: auto;">'
                    . e($r['correcao'] ?? '') . '</pre>';

                return new HtmlString($html);
            })
            ->modalSubmitActionLabel('Correr no servidor')
            ->action(function (array $arguments) {
                $chave = $arguments['chave'];
                $linha = $this->record->resultado($chave) ?? [];

                // Marca a linha como "a corrigir" e manda para a fila; a página
                // actualiza-se sozinha e mostra a saída quando acabar.
                $this->record->substituirResultado($chave, ['a_correr' => true] + $linha);
                CorrigirVelocidade::dispatch($this->record->fresh(), $chave);
                $this->record->refresh();

                Notification::make()
                    ->title('A corrigir em segundo plano')
                    ->body('A linha actualiza sozinha quando acabar, com a saída completa do servidor.')
                    ->info()
                    ->send();
            });
    }
}
