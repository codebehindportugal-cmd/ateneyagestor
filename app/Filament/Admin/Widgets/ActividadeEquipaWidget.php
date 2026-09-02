<?php

namespace App\Filament\Admin\Widgets;

use App\Models\TaskActivity;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

/**
 * Quem fez o quê, por ordem de acontecimento. É a resposta directa a "são
 * vários estagiários, quero ver quem mexeu em quê".
 */
class ActividadeEquipaWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static bool $isLazy = false;

    /** Só o administrador. Um estagiário nem vê isto no menu. */
    public static function canView(): bool
    {
        return Auth::user()?->isAdmin() === true;
    }

    protected function getTableHeading(): string
    {
        return 'Actividade da equipa';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TaskActivity::query()
                    ->with(['user', 'task.project'])
                    ->latest()
            )
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Quando')
                    ->dateTime('d/m/Y H:i')
                    ->description(fn (TaskActivity $record) => $record->created_at->diffForHumans())
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Quem')
                    ->badge()
                    ->placeholder('Sistema')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('O quê')
                    ->badge()
                    ->formatStateUsing(fn ($state) => TaskActivity::typeOptions()[$state] ?? $state)
                    ->color(fn ($state) => TaskActivity::typeColor($state)),

                Tables\Columns\TextColumn::make('task.title')
                    ->label('Tarefa')
                    ->wrap()
                    ->limit(60)
                    ->description(fn (TaskActivity $record) => $record->task?->project?->name)
                    ->searchable(),

                // Coluna calculada e nao um atributo: o `changes` e um array, e uma
                // TextColumn ligada a um array parte-o em varios valores em vez de
                // o mostrar como uma frase.
                Tables\Columns\TextColumn::make('detalhe')
                    ->label('Detalhe')
                    ->wrap()
                    ->getStateUsing(function (TaskActivity $record): string {
                        if ($record->body) {
                            return \Illuminate\Support\Str::limit($record->body, 90);
                        }

                        $changes = $record->changes;

                        if (is_array($changes) && isset($changes['campo'])) {
                            return $changes['campo'] . ': ' . ($changes['antes'] ?? '—') . ' → ' . ($changes['depois'] ?? '—');
                        }

                        return '—';
                    })
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Pessoa')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(TaskActivity::typeOptions())
                    ->multiple(),
            ])
            ->emptyStateHeading('Ainda sem actividade')
            ->emptyStateDescription('Assim que a equipa começar a mexer nas tarefas, aparece tudo aqui.')
            ->emptyStateIcon('heroicon-o-clock');
    }
}
