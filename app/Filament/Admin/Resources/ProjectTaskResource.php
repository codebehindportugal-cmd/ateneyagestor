<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProjectTaskResource\Pages;
use App\Filament\Admin\Support\TaskActions;
use App\Models\ProjectTask;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * A lista de tarefas de todos os projectos num sítio só.
 *
 * As tarefas já viviam dentro de cada projecto, mas para lá chegar era preciso
 * saber em que projecto estava a tarefa — o que quem entra pela primeira vez
 * não sabe. Esta é a porta de entrada: está no menu, mostra tudo, e é daqui
 * que se escolhe trabalho.
 */
class ProjectTaskResource extends Resource
{
    protected static ?string $model = ProjectTask::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Tarefas';
    protected static ?string $navigationGroup = 'Projectos';
    protected static ?int    $navigationSort  = 0;
    protected static ?string $modelLabel      = 'tarefa';
    protected static ?string $pluralModelLabel = 'tarefas';

    public static function getEloquentQuery(): Builder
    {
        // A rede do lado da consulta: um estagiário só recebe as tarefas dele e
        // as que estão no balcão. A regra está no scope do modelo, para não
        // haver três versões dela nas três listas.
        return parent::getEloquentQuery()->visivelPara(auth()->user());
    }

    /** O número no menu é o que a pessoa tem em mãos — não o total da casa. */
    public static function getNavigationBadge(): ?string
    {
        $abertas = static::getEloquentQuery()
            ->where('assigned_user_id', Auth::id())
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();

        return $abertas > 0 ? (string) $abertas : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected static function assignableUsers(): array
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    protected static function isAdmin(): bool
    {
        return Auth::user()?->isAdmin() === true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('project_id')
                ->label('Projecto')
                ->relationship('project', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('title')
                ->label('Tarefa')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\Select::make('assigned_user_id')
                ->label('Responsável')
                ->options(fn () => static::assignableUsers())
                ->searchable()
                ->placeholder('Por atribuir')
                ->disabled(fn () => ! static::isAdmin())
                ->dehydrated(fn () => static::isAdmin())
                ->helperText(fn () => static::isAdmin()
                    ? 'Quem fica encarregue. Fica registado no histórico da tarefa.'
                    : 'Só o administrador distribui as tarefas.'),

            Forms\Components\Select::make('status')
                ->label('Estado')
                ->options(ProjectTask::statusOptions())
                ->default('pending')
                ->required(),

            Forms\Components\DatePicker::make('due_date')
                ->label('Prazo')
                ->displayFormat('d/m/Y')
                ->native(false),

            Forms\Components\TextInput::make('estimated_hours')
                ->label('Estimativa (h)')
                ->numeric()
                ->step(0.25)
                ->minValue(0)
                ->helperText('Quanto se acha que demora. Fica lado a lado com as horas reais — é da diferença que se aprende a estimar.'),

            Forms\Components\TextInput::make('hours')
                ->label('Horas')
                ->numeric()
                ->step(0.25)
                ->minValue(0)
                ->helperText('Horas de trabalho registadas nesta tarefa.'),

            Forms\Components\Textarea::make('description')
                ->label('Descrição')
                ->rows(8)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $admin = static::isAdmin();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['project', 'assignedUser']))
            ->defaultSort('project_id')
            // Mesma leitura que a lista dentro do projecto (ManageProjectTasks):
            // título em cima, chips por baixo, horas à direita e a descrição
            // num painel que abre. A única diferença é o chip do projecto, que
            // aqui faz falta e lá não.
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('title')
                            ->label('Tarefa')
                            ->searchable()
                            ->sortable()
                            ->wrap()
                            ->weight('semibold')
                            ->color(fn (ProjectTask $record) => $record->isDone() ? 'gray' : null)
                            ->extraAttributes(fn (?ProjectTask $record) => [
                                'class' => $record?->isDone() ? 'atv-feito' : '',
                            ]),

                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('project.name')
                                ->label('Projecto')
                                ->badge()
                                ->size('xs')
                                ->color('gray')
                                ->icon('heroicon-m-folder')
                                ->sortable()
                                ->searchable()
                                ->grow(false),

                            Tables\Columns\TextColumn::make('status')
                                ->label('Estado')
                                ->badge()
                                ->size('xs')
                                ->formatStateUsing(fn ($state) => ProjectTask::statusOptions()[$state] ?? $state)
                                ->color(fn ($state) => ProjectTask::statusColor($state))
                                ->sortable()
                                ->grow(false),

                            Tables\Columns\TextColumn::make('assignedUser.name')
                                ->label('Responsável')
                                ->badge()
                                ->size('xs')
                                ->icon('heroicon-m-user')
                                ->color(fn ($state) => $state ? 'primary' : 'gray')
                                ->placeholder('Por escolher')
                                ->sortable()
                                ->searchable()
                                ->grow(false),

                            Tables\Columns\TextColumn::make('due_date')
                                ->label('Prazo')
                                ->size('xs')
                                ->icon('heroicon-m-calendar-days')
                                ->date('d/m/Y')
                                ->tooltip(fn (ProjectTask $record) => $record->isOverdue()
                                    ? 'Prazo ultrapassado'
                                    : 'Prazo')
                                ->color(fn (ProjectTask $record) => $record->isOverdue() ? 'danger' : 'gray')
                                ->sortable()
                                ->grow(false),

                            Tables\Columns\TextColumn::make('completed_at')
                                ->label('Concluída em')
                                ->size('xs')
                                ->icon('heroicon-m-check-circle')
                                ->date('d/m/Y')
                                ->color('gray')
                                ->tooltip(fn (ProjectTask $record) => filled($record->completed_at)
                                    ? 'Concluída em ' . $record->completed_at->format('d/m/Y H:i')
                                        . ($record->completedBy?->name ? ' por ' . $record->completedBy->name : '')
                                    : null)
                                ->sortable()
                                ->grow(false),
                        ])->extraAttributes(['class' => 'atv-chips']),
                    ])->space(2),

                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('estimated_hours')
                            ->label('Estimativa')
                            ->badge()
                            ->size('xs')
                            ->color('gray')
                            ->tooltip('Estimativa')
                            ->formatStateUsing(fn ($state) => filled($state)
                                ? ProjectTask::formatarHoras($state) . ' est.'
                                : null)
                            ->sortable()
                            ->grow(false),

                        Tables\Columns\TextColumn::make('hours')
                            ->label('Horas reais')
                            ->badge()
                            ->size('xs')
                            ->color('info')
                            ->tooltip('Horas registadas')
                            ->formatStateUsing(fn ($state) => filled($state)
                                ? ProjectTask::formatarHoras($state) . ' reais'
                                : null)
                            ->sortable()
                            ->grow(false),
                    ])->grow(false)->extraAttributes(['class' => 'atv-horas']),
                ])->from('md'),

                Tables\Columns\Layout\Panel::make([
                    Tables\Columns\ViewColumn::make('description')
                        ->label('Descrição')
                        ->view('filament.task-description'),
                ])
                    ->collapsible()
                    ->visible(fn (?ProjectTask $record) => filled($record?->description)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Projecto')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('assigned_user_id')
                    ->label('Responsável')
                    ->options(fn () => static::assignableUsers())
                    ->multiple()
                    ->visible($admin),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(ProjectTask::statusOptions())
                    ->multiple(),

                Tables\Filters\Filter::make('atrasadas')
                    ->label('Em atraso')
                    ->query(fn (Builder $query) => $query
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '<', now())
                        ->whereNotIn('status', ProjectTask::NOT_OVERDUE_STATUSES)),
            ])
            ->actions([
                TaskActions::verDetalhe(),
                TaskActions::ficarCom(),
                Tables\Actions\ActionGroup::make([
                    TaskActions::toggleDone(),
                    TaskActions::toggleWaiting(),
                    TaskActions::anexos(),
                    TaskActions::comentar(),
                    TaskActions::historico(),
                    Tables\Actions\EditAction::make()->label('Editar'),
                    Tables\Actions\DeleteAction::make()->label('Apagar'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('atribuir')
                        ->label('Atribuir a…')
                        ->icon('heroicon-o-user-plus')
                        ->color('primary')
                        ->visible($admin)
                        ->form([
                            Forms\Components\Select::make('assigned_user_id')
                                ->label('Responsável')
                                ->options(fn () => static::assignableUsers())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each(fn (ProjectTask $task) => $task
                                ->forceFill(['assigned_user_id' => $data['assigned_user_id']])
                                ->save());

                            Notification::make()
                                ->success()
                                ->title('Tarefas atribuídas')
                                ->body($records->count() . ' tarefas passaram para ' . (User::find($data['assigned_user_id'])?->name ?? 'a pessoa escolhida') . '.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sem tarefas')
            ->emptyStateDescription('Nada nesta vista. Experimenta o separador "Por escolher".')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Admin\Support\AnexosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        // As paginas de criar e editar existem mesmo, em vez de se contar com
        // os modais: sem a rota `create` registada, o botao "Nova tarefa" da
        // listagem nao fazia nada.
        return [
            'index'  => Pages\ListProjectTasks::route('/'),
            'create' => Pages\CreateProjectTask::route('/create'),
            'edit'   => Pages\EditProjectTask::route('/{record}/edit'),
        ];
    }
}
