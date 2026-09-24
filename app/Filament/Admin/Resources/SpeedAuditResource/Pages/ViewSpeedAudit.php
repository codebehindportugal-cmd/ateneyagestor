<?php

namespace App\Filament\Admin\Resources\SpeedAuditResource\Pages;

use App\Filament\Admin\Resources\SpeedAuditResource;
use App\Services\Velocidade\AuditoriaVelocidade;
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
                @set_time_limit(0);

                try {
                    $resposta = app(AuditoriaVelocidade::class)->corrigir($this->record->server, $arguments['chave']);
                } catch (\Throwable $e) {
                    Notification::make()->title('A correcção falhou')->body($e->getMessage())->danger()->persistent()->send();

                    return;
                }

                $this->record->substituirResultado($arguments['chave'], $resposta['resultado']);
                $this->record->refresh();

                $estado = $resposta['resultado']['estado'];

                Notification::make()
                    ->title(match ($estado) {
                        'ok'    => 'Corrigido',
                        'aviso' => 'Correu — ficou melhor mas com aviso',
                        default => 'Correu, mas continua a falhar',
                    })
                    ->body(str($resposta['saida'])->limit(600)->toString())
                    ->color($estado === 'ok' ? 'success' : 'warning')
                    ->persistent()
                    ->send();
            });
    }
}
