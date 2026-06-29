<?php

namespace App\Filament\Admin\Resources\SyncProjectResource\Pages;

use App\Filament\Admin\Resources\SyncProjectResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditSyncProject extends EditRecord
{
    protected static string $resource = SyncProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadCode')
                ->label('Download codigo')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('gray')
                ->visible(fn () => filled($this->record->code_archive_path) || SyncProjectResource::supportsGeneratedPackage($this->record))
                ->action(function () {
                    if ($this->record->code_archive_path) {
                        if (! Storage::disk('public')->exists($this->record->code_archive_path)) {
                            Notification::make()
                                ->title('Arquivo nao encontrado')
                                ->body('Volta a anexar o ZIP/RAR do sincronizador neste registo.')
                                ->danger()
                                ->persistent()
                                ->send();

                            return null;
                        }

                        return Storage::disk('public')->download(
                            $this->record->code_archive_path,
                            $this->record->code_archive_name ?: basename($this->record->code_archive_path)
                        );
                    }

                    try {
                        return SyncProjectResource::downloadGeneratedPackage($this->record);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Nao foi possivel gerar o pacote')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        return null;
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
