<?php

namespace App\Filament\Admin\Resources\ProjectResource\Pages;

use App\Filament\Admin\Resources\ProjectResource;
use App\Models\ProjectTask;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ManageProjectTasks extends ManageRelatedRecords
{
    protected static string $resource = ProjectResource::class;

    protected static string $relationship = 'tasks';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $title = 'Tarefas';

    public static function getNavigationLabel(): string
    {
        return 'Tarefas';
    }

    public function getSubheading(): ?string
    {
        $project = $this->getOwnerRecord();
        $total   = $project->tasks()->count();

        if ($total === 0) {
            return 'Ainda não há tarefas registadas neste projecto.';
        }

        $done  = $project->tasks()->where('status', 'done')->count();
        $open  = $total - $done;
        $pct   = (int) round($done / $total * 100);
        $hours = (float) $project->tasks()->sum('hours');

        $line = "{$done} de {$total} concluídas ({$pct}%) · {$open} por fazer";

        if ($hours > 0) {
            $line .= ' · ' . number_format($hours, 2, ',', '.') . ' h registadas';
        }

        return $line;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('Tarefa')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\Select::make('status')
                ->label('Estado')
                ->options(ProjectTask::statusOptions())
                ->default('pending')
                ->required(),

            Forms\Components\DatePicker::make('due_date')
                ->label('Prazo')
                ->displayFormat('d/m/Y')
                ->native(false),

            Forms\Components\TextInput::make('hours')
                ->label('Horas')
                ->numeric()
                ->step(0.25)
                ->minValue(0)
                ->helperText('Horas de trabalho a facturar nesta tarefa.'),

            Forms\Components\Textarea::make('description')
                ->label('Notas')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('position')
            ->defaultSort('position')
            ->columns([
                Tables\Columns\IconColumn::make('status')
                    ->label('')
                    ->icon(fn (ProjectTask $record) => $record->isDone()
                        ? 'heroicon-s-check-circle'
                        : ($record->status === 'cancelled' ? 'heroicon-o-x-circle' : 'heroicon-o-clock'))
                    ->color(fn (ProjectTask $record) => ProjectTask::statusColor($record->status)),

                Tables\Columns\TextColumn::make('title')
                    ->label('Tarefa')
                    ->searchable()
                    ->wrap()
                    ->weight('medium')
                    ->color(fn (ProjectTask $record) => $record->isDone() ? 'gray' : null)
                    ->description(fn (ProjectTask $record) => $record->description),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ProjectTask::statusOptions()[$state] ?? $state)
                    ->color(fn ($state) => ProjectTask::statusColor($state)),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Prazo')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable()
                    ->color(fn (ProjectTask $record) => $record->isOverdue() ? 'danger' : null),

                Tables\Columns\TextColumn::make('hours')
                    ->label('Horas')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => $state === null ? null : number_format((float) $state, 2, ',', '.') . ' h')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Concluída em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->description(fn (ProjectTask $record) => $record->completedBy?->name),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(ProjectTask::statusOptions())
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('concluidas')
                    ->label('Concluídas')
                    ->placeholder('Todas')
                    ->trueLabel('Só as feitas')
                    ->falseLabel('Só as que faltam')
                    ->queries(
                        true: fn (Builder $query) => $query->where('status', 'done'),
                        false: fn (Builder $query) => $query->where('status', '!=', 'done'),
                        blank: fn (Builder $query) => $query,
                    ),

                Tables\Filters\Filter::make('atrasadas')
                    ->label('Em atraso')
                    ->query(fn (Builder $query) => $query
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '<', now())
                        ->whereNotIn('status', ['done', 'cancelled'])),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nova tarefa'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleDone')
                    ->label(fn (ProjectTask $record) => $record->isDone() ? 'Reabrir' : 'Concluir')
                    ->icon(fn (ProjectTask $record) => $record->isDone() ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-check')
                    ->color(fn (ProjectTask $record) => $record->isDone() ? 'gray' : 'success')
                    ->action(function (ProjectTask $record) {
                        $record->isDone() ? $record->reopen() : $record->markDone();

                        Notification::make()
                            ->success()
                            ->title($record->isDone() ? 'Tarefa concluída' : 'Tarefa reaberta')
                            ->send();
                    }),
                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make()->label('Apagar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('concluir')
                        ->label('Marcar como feitas')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->markDone())
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sem tarefas')
            ->emptyStateDescription('Adiciona as tarefas do projecto para saberes o que já está feito e o que falta.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }
}
