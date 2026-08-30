<?php

namespace App\Filament\Admin\Resources\ProjectResource\Pages;

use App\Filament\Admin\Resources\ProjectResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            // O worker do Claude corre fora daqui (PC, LXC no Proxmox, ou a
            // propria VPS) e vem buscar os pedidos por HTTP. Este e o token
            // que ele usa.
            Actions\Action::make('claudeWorkerToken')
                ->label('Token do worker')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Token do worker do Claude')
                ->modalDescription('Isto revoga o token anterior do worker. Depois de gerar, actualiza o .env da máquina onde o worker corre.')
                ->modalSubmitActionLabel('Gerar')
                ->action(function () {
                    $user = Auth::user();

                    $user->tokens()->where('name', 'claude_worker')->delete();
                    $token = $user->createToken('claude_worker')->plainTextToken;

                    Notification::make()
                        ->title('Token gerado — copia agora')
                        ->body(
                            "Só é mostrado uma vez. No .env da máquina do worker:\n\n"
                            . 'CLAUDE_PANEL_URL=' . rtrim(config('app.url'), '/') . "\n"
                            . "CLAUDE_PANEL_TOKEN={$token}"
                        )
                        ->success()
                        ->persistent()
                        ->send();
                }),
        ];
    }
}
