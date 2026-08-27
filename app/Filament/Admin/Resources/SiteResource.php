<?php

namespace App\Filament\Admin\Resources;

use App\Enums\BackupFrequency;
use App\Enums\ServerType;
use App\Filament\Admin\Resources\SiteResource\Pages;
use App\Models\Site;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * O SITE — o que efetivamente se copia. Vive dentro de um servidor.
 *
 * O tipo é editável: mudar de "plesk" para "wordpress" é corrigir um erro de
 * classificação, não criar outra coisa. (Antes estava bloqueado, e foi preciso
 * mexer na base de dados para corrigir 11 registos mal classificados.)
 */
class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Sites';

    protected static ?string $modelLabel = 'site';

    protected static ?string $pluralModelLabel = 'sites';

    protected static ?string $navigationGroup = 'Operacao';

    protected static ?int $navigationSort = 2;

    /**
     * Partilhado com o SitesRelationManager dentro do servidor, para os dois
     * sítios onde se edita um site não divergirem.
     */
    public static function formSchema(bool $withServer = true): array
    {
        return array_values(array_filter([
            Forms\Components\Section::make('Identificacao')
                ->columns(2)
                ->schema(array_values(array_filter([
                    $withServer
                        ? Forms\Components\Select::make('server_id')
                            ->label('Servidor')
                            ->relationship('server', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => ($record->label ?: $record->name) . ' — ' . $record->host)
                            ->searchable()
                            ->preload()
                            ->required()
                        : null,
                    Forms\Components\Select::make('client_id')
                        ->label('Cliente')
                        ->relationship('client', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\TextInput::make('name')
                        ->label('Identificador')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Pasta no NAS, dentro da do servidor — sem espaços, ex: jacfaria-com.'),
                    Forms\Components\TextInput::make('domain')
                        ->label('Domínio')
                        ->placeholder('jacfaria.com'),
                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->options([
                            ServerType::WordPress->value  => ServerType::WordPress->label(),
                            ServerType::VpsLaravel->value => ServerType::VpsLaravel->label(),
                            ServerType::Plesk->value      => ServerType::Plesk->label(),
                            ServerType::Cpanel->value     => ServerType::Cpanel->label(),
                        ])
                        ->required()
                        ->live()
                        ->helperText('Plesk só funciona em máquinas que tenham mesmo o Plesk instalado.'),
                    Forms\Components\Toggle::make('is_active')->label('Ativo')->default(true),
                    Forms\Components\Select::make('backup_frequency')
                        ->label('Frequência de backup')
                        ->options(BackupFrequency::options())
                        ->default(BackupFrequency::Daily->value)
                        ->required()
                        ->helperText('Os mensais só correm no dia 1. O agente corre todas as noites e salta os restantes.'),
                ]))),

            Forms\Components\Section::make('WordPress')
                ->visible(fn (Get $get) => $get('type') === ServerType::WordPress->value)
                ->schema([
                    Forms\Components\TextInput::make('wp_root')
                        ->label('Caminho do WordPress (wp_root)')
                        ->required(fn (Get $get) => $get('type') === ServerType::WordPress->value)
                        ->placeholder('/var/www/exemplo.com/public_html')
                        ->helperText('Diretoria que contém o wp-config.php. As credenciais da BD são lidas de lá em tempo real.')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('VPS + Laravel')
                ->columns(2)
                ->visible(fn (Get $get) => $get('type') === ServerType::VpsLaravel->value)
                ->schema([
                    Forms\Components\TextInput::make('app_path')
                        ->label('Caminho da app (raiz do Laravel)')
                        ->required(fn (Get $get) => $get('type') === ServerType::VpsLaravel->value)
                        ->helperText('Ex: /var/www/acme-site — as credenciais da BD são lidas do .env de lá.'),
                    Forms\Components\TagsInput::make('storage_paths')
                        ->label('Pastas extra a arquivar')
                        ->placeholder('/var/www/acme-site/storage/app'),
                ]),

            Forms\Components\Section::make('Plesk')
                ->columns(2)
                ->visible(fn (Get $get) => $get('type') === ServerType::Plesk->value)
                ->schema([
                    Forms\Components\TagsInput::make('plesk_backup_args')
                        ->label('Flags extra do pleskbackup')
                        ->placeholder('--rotation=0')
                        ->helperText('O domínio usado é o do campo "Domínio" acima.'),
                ]),

            Forms\Components\Section::make('cPanel')
                ->columns(2)
                ->visible(fn (Get $get) => $get('type') === ServerType::Cpanel->value)
                ->schema([
                    Forms\Components\TextInput::make('api_port')->label('Porto da API (HTTPS)')->numeric()->default(2083),
                    Forms\Components\TextInput::make('backup_dest')->label('Destino do backup')->default('homedir'),
                    Forms\Components\TextInput::make('poll_interval_seconds')->label('Intervalo de verificacao (s)')->numeric()->default(30),
                    Forms\Components\TextInput::make('max_wait_seconds')->label('Tempo maximo de espera (s)')->numeric()->default(1800),
                ]),

            Forms\Components\Section::make('Retencao (opcional)')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('retention_keep_days')
                        ->label('Manter X dias')->numeric()
                        ->helperText('Em branco = usa a do servidor, e depois a global do agente.'),
                    Forms\Components\TextInput::make('retention_keep_min_copies')
                        ->label('Manter sempre no minimo X copias')->numeric(),
                    Forms\Components\TextInput::make('retention_max_copies')
                        ->label('Guardar no máximo X cópias')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Apaga as mais antigas assim que passarem deste número, independentemente da idade. Em branco = sem limite.'),
                ]),

            Forms\Components\Textarea::make('notes')->label('Notas internas')->columnSpanFull(),
        ]));
    }

    public static function form(Form $form): Form
    {
        return $form->schema(static::formSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Site')
                    ->description(fn (Site $record) => $record->domain)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')->label('Cliente')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('server.label')
                    ->label('Servidor')
                    ->formatStateUsing(fn ($state, Site $record) => $state ?: $record->server?->name)
                    ->description(fn (Site $record) => $record->server?->host)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Tipo')->badge(),
                Tables\Columns\TextColumn::make('latestBackupRun.status')
                    ->label('Ultimo backup')
                    ->badge()
                    ->color(fn ($state) => $state?->color() ?? 'gray')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? 'Sem dados'),
                Tables\Columns\TextColumn::make('latestBackupRun.size_bytes')
                    ->label('Tamanho')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?int $state) => $state ? number_format($state / 1048576, 1) . ' MB' : '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('backup_frequency')
                    ->label('Frequência')
                    ->badge()
                    ->formatStateUsing(fn (?BackupFrequency $state) => $state?->label() ?? '—')
                    ->color(fn (?BackupFrequency $state) => $state === BackupFrequency::Monthly ? 'gray' : 'info')
                    ->description(fn ($record) => $record->retention_max_copies
                        ? 'guarda ' . $record->retention_max_copies
                        : null),
                Tables\Columns\IconColumn::make('is_active')->label('Ativo')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('server_id')
                    ->label('Servidor')
                    ->relationship('server', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')->label('Tipo')->options([
                    ServerType::WordPress->value  => ServerType::WordPress->label(),
                    ServerType::VpsLaravel->value => ServerType::VpsLaravel->label(),
                    ServerType::Plesk->value      => ServerType::Plesk->label(),
                    ServerType::Cpanel->value     => ServerType::Cpanel->label(),
                ]),
                Tables\Filters\SelectFilter::make('backup_frequency')
                    ->label('Frequência')
                    ->options(BackupFrequency::options()),
                Tables\Filters\TernaryFilter::make('is_active')->label('Ativo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Admin\Resources\SiteResource\RelationManagers\BackupRunsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            'edit'   => Pages\EditSite::route('/{record}/edit'),
        ];
    }
}
