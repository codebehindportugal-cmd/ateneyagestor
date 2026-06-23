<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AgentResource\Pages;
use App\Models\Agent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'Agentes (Pi)';

    protected static ?string $modelLabel = 'agente';

    protected static ?string $pluralModelLabel = 'agentes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificacao')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')->label('Nome')->required()->helperText('Ex: "Pi de casa".'),
                    Forms\Components\TextInput::make('slug')->label('Identificador')->disabled()->dehydrated(false),
                ]),
            Forms\Components\Section::make('Disco e retencao')
                ->description('Enviado ao Pi como o bloco "global" de GET /api/agent/config.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('backup_root')
                        ->label('Caminho do disco no Pi')
                        ->required()
                        ->default('/mnt/backup-disk')
                        ->helperText('Tem de corresponder ao ponto de montagem real do disco no Pi.'),
                    Forms\Components\TextInput::make('log_level')
                        ->label('Nivel de log')
                        ->default('INFO')
                        ->helperText('INFO, DEBUG, WARNING ou ERROR.'),
                    Forms\Components\TextInput::make('retention_keep_days')
                        ->label('Manter backups durante (dias)')
                        ->numeric()
                        ->required()
                        ->default(14),
                    Forms\Components\TextInput::make('retention_keep_min_copies')
                        ->label('Manter sempre no minimo (copias)')
                        ->numeric()
                        ->required()
                        ->default(3),
                ]),
            Forms\Components\Section::make('Notificacoes de falha (opcional)')
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('notify_webhook_enabled')->label('Ativar webhook')->live(),
                    Forms\Components\TextInput::make('notify_webhook_url')
                        ->label('URL do webhook')
                        ->url()
                        ->visible(fn (Forms\Get $get) => $get('notify_webhook_enabled')),
                    Forms\Components\Toggle::make('notify_sendmail_enabled')->label('Ativar sendmail')->live(),
                    Forms\Components\TextInput::make('notify_sendmail_to')
                        ->label('Email de destino')
                        ->email()
                        ->visible(fn (Forms\Get $get) => $get('notify_sendmail_enabled')),
                ]),
            Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nome')->searchable(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn (string $state) => $state === 'online' ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state) => $state === 'online' ? 'Online' : 'Offline'),
                Tables\Columns\TextColumn::make('last_seen_at')->label('Ultimo contacto')->dateTime('d/m/Y H:i')->placeholder('Nunca'),
                Tables\Columns\TextColumn::make('servers_count')->label('Servidores')->counts('servers'),
            ])
            ->actions([
                Tables\Actions\Action::make('generateToken')
                    ->label('Gerar novo token')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Isto revoga qualquer token anterior deste agente. Vais precisar de atualizar agent_config.yaml no Pi com o novo token.')
                    ->action(function (Agent $record) {
                        $record->tokens()->delete();
                        $plainTextToken = $record->createToken('agent_sync')->plainTextToken;

                        Notification::make()
                            ->title('Token gerado -- copia agora')
                            ->body("Este token so e mostrado uma vez:\n\n{$plainTextToken}\n\nCola-o em agent_config.yaml no Pi, no campo api.token.")
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
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgents::route('/'),
            'create' => Pages\CreateAgent::route('/create'),
            'edit' => Pages\EditAgent::route('/{record}/edit'),
        ];
    }
}
