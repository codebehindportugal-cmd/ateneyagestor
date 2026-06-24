<?php

namespace App\Filament\Admin\Resources;

use App\Enums\SyncStatus;
use App\Filament\Admin\Resources\SyncProjectResource\Pages;
use App\Filament\Admin\Resources\SyncProjectResource\RelationManagers\SyncRunsRelationManager;
use App\Models\SyncProject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class SyncProjectResource extends Resource
{
    protected static ?string $model = SyncProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Sincronizadores';

    protected static ?string $modelLabel = 'sincronizador';

    protected static ?string $pluralModelLabel = 'sincronizadores';

    protected static ?string $navigationGroup = 'Integrações';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificação')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->placeholder('Ex: PHC → Faustino Clemente'),
                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->options(SyncProject::typeOptions())
                        ->required(),
                    Forms\Components\TextInput::make('site_url')
                        ->label('URL do site WooCommerce')
                        ->url()
                        ->placeholder('https://exemplo.pt'),
                    Forms\Components\Select::make('client_id')
                        ->label('Cliente associado')
                        ->relationship('client', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Forms\Components\Toggle::make('is_active')->label('Ativo')->default(true),
                ]),
            Forms\Components\Section::make('Runner local')
                ->description('Configuração para executar o script directamente neste servidor (gestao.ateneya.com).')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('runner_script_path')
                        ->label('Caminho do script')
                        ->placeholder('syncer/wintouch_woo/main.py')
                        ->helperText('Relativo à raiz do projecto backup-manager.')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('runner_schedule')
                        ->label('Schedule (cron)')
                        ->placeholder('0 */3 * * *')
                        ->helperText('Expressão cron para execução automática. Ex: cada 3h = "0 */3 * * *". Deixa em branco para não agendar.')
                        ->columnSpanFull(),
                ]),
            Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Tipo')
                    ->formatStateUsing(fn ($state) => SyncProject::typeOptions()[$state] ?? $state)
                    ->badge()->color('info'),
                Tables\Columns\TextColumn::make('site_url')->label('Site')->url(fn ($record) => $record->site_url)->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('client.name')->label('Cliente')->placeholder('-'),
                Tables\Columns\TextColumn::make('status')->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'ok' => 'success',
                        'error' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'ok' => 'OK',
                        'error' => 'Erro',
                        default => 'Nunca correu',
                    }),
                Tables\Columns\TextColumn::make('last_run_at')->label('Última execução')
                    ->dateTime('d/m/Y H:i')->placeholder('Nunca')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Ativo')->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('run_now')
                    ->label('Correr agora')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Correr sincronizador')
                    ->modalDescription(fn (SyncProject $record) => "Vai executar \"{$record->name}\" agora em background. Podes acompanhar o resultado no histórico de runs.")
                    ->visible(fn (SyncProject $record) => filled($record->runner_script_path))
                    ->action(function (SyncProject $record) {
                        Artisan::queue("sync:run {$record->slug}");
                        Notification::make()
                            ->title('Sincronizador iniciado')
                            ->body("O sync \"{$record->name}\" foi colocado em fila. Resultado disponível em breve no histórico.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('generateToken')
                    ->label('Gerar token')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Isto revoga qualquer token anterior deste sincronizador. Atualiza o BACKUP_MANAGER_TOKEN no .env do script Python/C#.')
                    ->action(function (SyncProject $record) {
                        $record->tokens()->delete();
                        $plainTextToken = $record->createToken('sync_reporter')->plainTextToken;

                        Notification::make()
                            ->title('Token gerado — copia agora')
                            ->body(
                                "Token (só mostrado uma vez):\n\n{$plainTextToken}\n\n".
                                "Cola no .env do script:\n".
                                "BACKUP_MANAGER_URL=http://backup-manager.test\n".
                                "BACKUP_MANAGER_TOKEN={$plainTextToken}"
                            )
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_run_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            SyncRunsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSyncProjects::route('/'),
            'create' => Pages\CreateSyncProject::route('/create'),
            'edit' => Pages\EditSyncProject::route('/{record}/edit'),
        ];
    }
}
