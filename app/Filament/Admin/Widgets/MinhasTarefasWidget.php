<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\ProjectResource;
use App\Models\ProjectTask;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * O primeiro que se vê ao entrar: o que é que eu tenho para fazer.
 * Para um estagiário são as tarefas dele; para o administrador é o que está
 * distribuído pela equipa e ainda por fechar.
 */
class MinhasTarefasWidget extends BaseWidget
{
    protected static ?int $sort = -2;

    protected int | string | array $columnSpan = 'full';

    protected static bool $isLazy = false;

    protected function getTableHeading(): string
    {
        return Auth::user()?->isAdmin()
            ? 'Tarefas da equipa por fechar'
            : 'As minhas tarefas';
    }

    public function table(Table $table): Table
    {
        $admin = Auth::user()?->isAdmin() === true;

        return $table
            ->query(
                ProjectTask::query()
                    ->with(['project', 'assignedUser'])
                    ->whereNotIn('status', ['done', 'cancelled'])
                    ->when(
                        $admin,
                        // Ao administrador interessa o que já tem dono — o que
                        // ainda não foi distribuído aparece no próprio projecto.
                        fn (Builder $query) => $query->whereNotNull('assigned_user_id'),
                        fn (Builder $query) => $query->where('assigned_user_id', Auth::id()),
                    )
                    ->orderByRaw('due_date IS NULL')
                    ->orderBy('due_date')
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

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Responsável')
                    ->badge()
                    ->placeholder('Por atribuir')
                    ->visible($admin)
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ProjectTask::statusOptions()[$state] ?? $state)
                    ->color(fn ($state) => ProjectTask::statusColor($state)),

                Tables\Columns\TextColumn::make('estimated_hours')
                    ->label('Estimativa')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => ProjectTask::formatarHoras($state))
                    ->color('gray'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Prazo')
                    ->date('d/m/Y')
                    ->placeholder('sem prazo')
                    ->color(fn (ProjectTask $record) => $record->isOverdue() ? 'danger' : null)
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('abrir')
                    ->label('Abrir')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ProjectTask $record) => $record->project
                        ? ProjectResource::getUrl('tasks', ['record' => $record->project])
                        : null),
            ])
            ->emptyStateHeading('Nada por fazer')
            ->emptyStateDescription(Auth::user()?->isAdmin()
                ? 'Não há tarefas atribuídas por fechar.'
                : 'Não tens tarefas atribuídas de momento.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
