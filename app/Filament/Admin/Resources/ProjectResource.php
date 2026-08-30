<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon  = 'heroicon-o-folder-open';
    protected static ?string $navigationLabel = 'Projectos';
    protected static ?string $navigationGroup = 'Projectos';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $modelLabel      = 'projecto';
    protected static ?string $pluralModelLabel = 'projectos';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount([
            'tasks',
            'tasks as tasks_done_count' => fn (Builder $query) => $query->where('status', 'done'),
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificação')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, Forms\Set $set, $record) =>
                            $record === null ? $set('slug', Str::slug($state)) : null
                        ),
                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\Toggle::make('is_internal')
                        ->label('Projecto interno')
                        ->helperText('Produto/software da Codebehind (não construído para cliente externo).')
                        ->default(false),
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options(Project::statusOptions())
                        ->required()
                        ->default('active'),
                    Forms\Components\TextInput::make('url')
                        ->label('URL')
                        ->url()
                        ->placeholder('https://exemplo.pt')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Associações')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->options(Project::typeOptions())
                        ->required()
                        ->default('other'),
                    Forms\Components\Select::make('client_id')
                        ->label('Cliente')
                        ->relationship('client', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Forms\Components\Select::make('server_id')
                        ->label('Servidor')
                        ->relationship('server', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->host})"),
                ]),

            Forms\Components\Section::make('Código')
                ->description('De onde é que o Claude lê o código quando se manda uma tarefa deste projecto.')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('code_source')
                        ->label('Fonte')
                        ->options(Project::codeSourceOptions())
                        ->default('none')
                        ->required()
                        ->live()
                        ->helperText('Sem código ele continua a responder — só planeia em vez de ler ficheiros.'),

                    Forms\Components\TextInput::make('code_path')
                        ->label('Pasta local')
                        ->placeholder('C:\\laragon\\www\\exemplo')
                        ->helperText('Na máquina que corre o worker. Não precisa de ser um repositório git.')
                        ->required(fn (Forms\Get $get) => $get('code_source') === 'local')
                        ->visible(fn (Forms\Get $get) => $get('code_source') === 'local'),

                    Forms\Components\Select::make('site_id')
                        ->label('Site a copiar')
                        ->relationship('site', 'name')
                        ->searchable()
                        ->preload()
                        ->helperText('O caminho sai do wp_root (ou app_path) do site. Traz-se uma cópia só de leitura do tema e dos plugins — nunca uploads, wp-config.php ou .env.')
                        ->getOptionLabelFromRecordUsing(fn ($record) => trim("{$record->name} · " . ($record->domain ?: '') . ' · ' . ($record->wp_root ?: $record->app_path ?: 'sem caminho'), ' ·'))
                        ->required(fn (Forms\Get $get) => $get('code_source') === 'remote')
                        ->visible(fn (Forms\Get $get) => $get('code_source') === 'remote'),
                ]),

            Forms\Components\Textarea::make('notes')
                ->label('Notas')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Projecto')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\IconColumn::make('is_internal')
                    ->label('Interno')
                    ->boolean()
                    ->trueIcon('heroicon-o-building-office-2')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('info')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => Project::typeOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'laravel'     => 'danger',
                        'wordpress'   => 'info',
                        'woocommerce' => 'warning',
                        'sync'        => 'success',
                        default       => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => Project::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active'      => 'success',
                        'development' => 'warning',
                        'suspended'   => 'gray',
                        default       => 'gray',
                    }),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('server.name')
                    ->label('Servidor')
                    ->placeholder('—')
                    ->description(fn ($record) => $record->server?->host)
                    ->searchable(),
                Tables\Columns\TextColumn::make('tasks_count')
                    ->label('Tarefas')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $state
                        ? "{$record->tasks_done_count}/{$state} · {$record->progressPercent()}%"
                        : '—')
                    ->color(fn ($state, $record) => match (true) {
                        ! $state                                        => 'gray',
                        (int) $record->tasks_done_count === (int) $state => 'success',
                        (int) $record->tasks_done_count > 0             => 'warning',
                        default                                         => 'danger',
                    })
                    ->tooltip(fn ($record) => $record->tasks_count
                        ? "{$record->tasks_done_count} feitas · " . ($record->tasks_count - $record->tasks_done_count) . ' por fazer'
                        : 'Sem tarefas registadas')
                    ->url(fn (Project $record) => static::getUrl('tasks', ['record' => $record]))
                    ->sortable(),

                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab()
                    ->placeholder('—')
                    ->limit(40),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(Project::statusOptions()),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(Project::typeOptions()),
                Tables\Filters\TernaryFilter::make('is_internal')
                    ->label('Interno'),
            ])
            ->actions([
                Tables\Actions\Action::make('tasks')
                    ->label('Tarefas')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('info')
                    ->url(fn (Project $record) => static::getUrl('tasks', ['record' => $record])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('is_internal', 'desc');
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\EditProject::class,
            Pages\ManageProjectTasks::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit'   => Pages\EditProject::route('/{record}/edit'),
            'tasks'  => Pages\ManageProjectTasks::route('/{record}/tasks'),
        ];
    }
}
