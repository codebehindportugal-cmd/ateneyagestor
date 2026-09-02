<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\ProjectResource;
use App\Filament\Admin\Support\TaskActions;
use App\Models\ProjectTask;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * O balcão do trabalho por distribuir. Quem quiser, chama a tarefa a si e ela
 * passa a ser dele — fica registado quem a escolheu e quando.
 */
class TarefasPorEscolherWidget extends BaseWidget
{
    protected static ?int $sort = -1;

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
                    ->porEscolher()
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

                Tables\Columns\TextColumn::make('estimated_hours')
                    ->label('Estimativa')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => ProjectTask::formatarHoras($state))
                    ->badge()
                    ->color('gray')
                    ->sortable(),

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
                // Mesmas acções da lista de tarefas — ver App\Filament\Admin\Support\TaskActions.
                TaskActions::verDetalhe(),
                TaskActions::ficarCom(),
                Tables\Actions\Action::make('abrir')
                    ->label('No projecto')
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
