<?php

namespace App\Filament\Admin\Resources\ProjectResource\Pages;

use App\Filament\Admin\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    /**
     * Sem isto, o Filament inventa o rotulo a partir do nome da classe e a
     * barra lateral mostra "Edit Project" em ingles, num painel que esta' todo
     * em portugues. O `getRecordSubNavigation()` do ProjectResource e' quem
     * desenha estes itens.
     */
    protected static ?string $navigationLabel = 'Editar';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('tasks')
                ->label('Tarefas')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('info')
                ->url(fn () => ProjectResource::getUrl('tasks', ['record' => $this->getRecord()])),
            Actions\DeleteAction::make(),
        ];
    }
}
