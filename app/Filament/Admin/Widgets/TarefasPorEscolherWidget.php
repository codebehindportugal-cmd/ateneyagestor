<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\ProjectResource;
use App\Models\ProjectTask;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

/**
 * O balcão do trabalho por distribuir. Quem quiser, chama a tarefa a si e ela
 * passa a ser dele — fica registado quem a escolheu e quando.
 */
class TarefasPorEscolherWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static bool $isLazy = false;

    protected function getTableHeading(): string
    {
        return 'Tarefas por escolher';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProjectTask::query()
                    ->with('project')
                    ->whereNull('assigned_user_id')
                    ->whereNotIn('status', ['done', 'cancelled'])
                    ->orderBy('project_id')
                    ->orderBy('position')
            )
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Tarefa')
                    ->wrap()
                    ->weight('medium')
                    ->description(fn (ProjectTask $record) => $record->project?->name)
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('O que é')
                    ->wrap()
                    ->limit(160)
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Prazo')
                    ->date('d/m/Y')
                    ->placeholder('sem prazo')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Projecto')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('ficarCom')
                    ->label('Ficar com esta')
                    ->icon('heroicon-o-hand-raised')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading(fn (ProjectTask $record) => 'Ficar com · ' . $record->title)
                    ->modalDescription('A tarefa passa a ser tua e fica a Em curso. Lê a descrição toda antes de decidir.')
                    ->modalSubmitActionLabel('Fico com ela')
                    ->action(function (ProjectTask $record) {
                        // Duas pessoas podem carregar no botão ao mesmo tempo: quem
                        // chega primeiro fica com ela, o segundo é avisado.
                        $ficou = ProjectTask::where('id', $record->id)
                            ->whereNull('assigned_user_id')
                            ->update([
                                'assigned_user_id' => Auth::id(),
                                'status'           => 'in_progress',
                                'updated_at'       => now(),
                            ]);

                        if ($ficou === 0) {
                            Notification::make()
                                ->warning()
                                ->title('Já não estava livre')
                                ->body('Outra pessoa ficou com esta tarefa entretanto. Escolhe outra.')
                                ->send();

                            return;
                        }

                        $record->refresh()->logActivity('assigned', 'Escolheu esta tarefa.', [
                            'campo'  => 'Responsável',
                            'antes'  => 'por atribuir',
                            'depois' => Auth::user()?->name ?? '—',
                        ]);

                        Notification::make()
                            ->success()
                            ->title('A tarefa é tua')
                            ->body('Passou a Em curso. Vai comentando o que fores fazendo.')
                            ->send();
                    }),

                Tables\Actions\Action::make('abrir')
                    ->label('Ver no projecto')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (ProjectTask $record) => $record->project
                        ? ProjectResource::getUrl('tasks', ['record' => $record->project])
                        : null),
            ])
            ->emptyStateHeading('Está tudo distribuído')
            ->emptyStateDescription('Não há tarefas sem dono de momento.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
