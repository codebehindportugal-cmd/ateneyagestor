<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\ProjectTask;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Equipa';

    protected static ?string $modelLabel = 'utilizador';

    protected static ?string $pluralModelLabel = 'equipa';

    protected static ?string $navigationGroup = 'Administracao';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount([
            'assignedTasks',
            'assignedTasks as open_tasks_count' => fn (Builder $query) => $query
                ->whereNotIn('status', ['done', 'cancelled']),
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Quem é')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')->label('Nome')->required(),
                    Forms\Components\TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('job_title')
                        ->label('Função')
                        ->placeholder('Ex: Estagiário de front-end')
                        ->helperText('Só para se saber quem é quem na lista.'),
                    Forms\Components\TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->helperText('Deixa em branco para nao alterar.'),
                ]),

            Forms\Components\Section::make('Acesso')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('role')
                        ->label('Papel')
                        ->options(User::roleOptions())
                        ->default(User::ROLE_ESTAGIARIO)
                        ->required()
                        ->live()
                        ->helperText(fn (Forms\Get $get) => $get('role') === User::ROLE_ADMIN
                            ? 'Vê e mexe em tudo: clientes, facturação, servidores, credenciais e o Claude.'
                            : 'Só vê os projectos onde tem tarefas suas, e dentro deles só as tarefas que lhe foram atribuídas. Não vê facturação, servidores nem credenciais.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Conta activa')
                        ->default(true)
                        ->helperText('Desliga quando o estágio acabar. A conta deixa de entrar mas o histórico do que fez fica todo.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn (User $record) => $record->job_title),

                Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Papel')
                    ->badge()
                    ->formatStateUsing(fn ($state) => User::roleOptions()[$state] ?? $state)
                    ->color(fn ($state) => User::roleColor($state)),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),

                Tables\Columns\TextColumn::make('open_tasks_count')
                    ->label('Tarefas abertas')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->tooltip(fn (User $record) => "{$record->assigned_tasks_count} tarefas atribuídas ao todo"),

                Tables\Columns\TextColumn::make('created_at')->label('Criado')->dateTime('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Papel')
                    ->options(User::roleOptions()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Conta activa'),
            ])
            ->actions([
                Tables\Actions\Action::make('tarefas')
                    ->label('Tarefas')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('info')
                    ->modalHeading(fn (User $record) => 'Tarefas de ' . $record->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->modalContent(fn (User $record) => view('filament.user-tasks-modal', [
                        'user'  => $record,
                        'tasks' => ProjectTask::query()
                            ->with('project')
                            ->where('assigned_user_id', $record->id)
                            ->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END")
                            ->orderBy('due_date')
                            ->limit(50)
                            ->get(),
                    ])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('role');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
