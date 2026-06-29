<?php

namespace App\Filament\Admin\Resources;

use App\Enums\BackupStatus;
use App\Enums\ServerType;
use App\Filament\Admin\Resources\ServerResource\Pages;
use App\Models\Server;
use App\Enums\SecurityStatus;
use App\Services\BackupService;
use App\Services\SecurityScanService;
use App\Services\SshService;
use Illuminate\Support\Facades\Artisan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationLabel = 'Servidores';

    protected static ?string $modelLabel = 'servidor';

    protected static ?string $pluralModelLabel = 'servidores';

    protected static ?string $navigationGroup = 'Operacao';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificacao')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('client_id')
                        ->label('Cliente')
                        ->relationship('client', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('agent_id')
                        ->label('Agente (Pi) responsavel')
                        ->relationship('agent', 'name')
                        ->searchable()
                        ->preload()
                        ->helperText('Qual Pi/agente vai puxar este backup. Deixa em branco se so tiveres um agente.'),
                    Forms\Components\TextInput::make('name')
                        ->label('Nome (identificador)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Usado como nome da pasta no disco do Pi -- sem espacos, ex: vps-acme-site.'),
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
                        ->disabledOn('edit')
                        ->helperText('Nao editavel depois de criado -- cria um novo registo se precisares de mudar o tipo.'),
                    Forms\Components\Toggle::make('is_active')->label('Ativo')->default(true),
                ]),

            Forms\Components\Section::make('Ligacao')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('host')->label('Host / IP')->required(),
                    Forms\Components\TextInput::make('port')->label('Porto SSH')->numeric()->default(22)
                        ->visible(fn (Get $get) => in_array($get('type'), [ServerType::VpsLaravel->value, ServerType::Plesk->value])),
                    Forms\Components\TextInput::make('user')->label('Utilizador SSH')
                        ->visible(fn (Get $get) => in_array($get('type'), [ServerType::VpsLaravel->value, ServerType::Plesk->value]))
                        ->required(fn (Get $get) => in_array($get('type'), [ServerType::VpsLaravel->value, ServerType::Plesk->value])),
                ]),

            Forms\Components\Section::make('WordPress')
                ->columns(2)
                ->visible(fn (Get $get) => $get('type') === ServerType::WordPress->value)
                ->schema([
                    Forms\Components\TextInput::make('wp_root')
                        ->label('Caminho do WordPress (wp_root)')
                        ->required(fn (Get $get) => $get('type') === ServerType::WordPress->value)
                        ->placeholder('/var/www/exemplo.com/public_html')
                        ->helperText('Diretoria que contém o wp-config.php. As credenciais da BD são lidas do wp-config.php em tempo real.')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('VPS + Laravel')
                ->columns(2)
                ->visible(fn (Get $get) => $get('type') === ServerType::VpsLaravel->value)
                ->schema([
                    Forms\Components\TextInput::make('app_path')
                        ->label('Caminho da app (raiz do Laravel)')
                        ->required(fn (Get $get) => $get('type') === ServerType::VpsLaravel->value)
                        ->helperText('Ex: /var/www/acme-site -- as credenciais da BD sao lidas do .env aqui em tempo real.'),
                    Forms\Components\TagsInput::make('storage_paths')
                        ->label('Pastas a arquivar (relativas ao caminho da app)')
                        ->default(['storage/app', 'storage/logs'])
                        ->placeholder('storage/app'),
                ]),

            Forms\Components\Section::make('Plesk')
                ->columns(2)
                ->visible(fn (Get $get) => $get('type') === ServerType::Plesk->value)
                ->schema([
                    Forms\Components\TextInput::make('domain')
                        ->label('Dominio a fazer backup')
                        ->required(fn (Get $get) => $get('type') === ServerType::Plesk->value),
                    Forms\Components\TagsInput::make('plesk_backup_args')
                        ->label('Flags extra do pleskbackup')
                        ->placeholder('--rotation=0'),
                ]),

            Forms\Components\Section::make('cPanel')
                ->columns(2)
                ->visible(fn (Get $get) => $get('type') === ServerType::Cpanel->value)
                ->schema([
                    Forms\Components\TextInput::make('api_port')->label('Porto da API (HTTPS)')->numeric()->default(2083),
                    Forms\Components\TextInput::make('backup_dest')->label('Destino do backup')->default('homedir'),
                    Forms\Components\TextInput::make('poll_interval_seconds')->label('Intervalo de verificacao (segundos)')->numeric()->default(30),
                    Forms\Components\TextInput::make('max_wait_seconds')->label('Tempo maximo de espera (segundos)')->numeric()->default(1800),
                ]),

            Forms\Components\Section::make('Segredos (apenas no Pi)')
                ->description('A chave SSH / token de API NUNCA sao guardados aqui -- ficam so no secrets.yaml do Pi, associados por esta referencia.')
                ->schema([
                    Forms\Components\TextInput::make('agent_secret_ref')
                        ->label('Referencia do segredo (agent_secret_ref)')
                        ->helperText('Se deixares em branco, usa o nome do servidor. Tem de corresponder a uma entrada em secrets.yaml no Pi.'),
                ]),

            Forms\Components\Section::make('Acesso direto SSH (deste painel)')
                ->description('Para conseguires correr comandos nos servidores diretamente deste painel. A chave fica no teu PC, não na base de dados.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('ssh_key_path')
                        ->label('Caminho da chave SSH privada')
                        ->placeholder('C:/Users/André Mendes/.ssh/id_rsa')
                        ->helperText('Caminho no teu PC (Laragon) para a chave privada que tem acesso ao VPS.'),
                    Forms\Components\TextInput::make('plesk_api_key')
                        ->label('Plesk API Key')
                        ->password()
                        ->revealable()
                        ->helperText('Opcional. Criado no Plesk: Ferramentas > API Keys.'),
                ]),

            Forms\Components\Section::make('Retencao (opcional)')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('retention_keep_days')->label('Manter X dias')->numeric()->helperText('Em branco = usa o valor global do agente.'),
                    Forms\Components\TextInput::make('retention_keep_min_copies')->label('Manter sempre no minimo X copias')->numeric(),
                ]),

            Forms\Components\Textarea::make('notes')->label('Notas internas')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                Tables\Columns\TextColumn::make('ping_status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'up'    => 'success',
                        'down'  => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'up'    => 'Online',
                        'down'  => 'Offline',
                        default => '?',
                    })
                    ->tooltip(fn (Server $record) => $record->ping_last_checked_at
                        ? 'Verificado ' . $record->ping_last_checked_at->diffForHumans()
                        : 'Nunca verificado'),
                Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.name')->label('Cliente')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Tipo')->badge(),
                Tables\Columns\TextColumn::make('host')->label('Host'),
                Tables\Columns\TextColumn::make('ping_response_ms')
                    ->label('Latência')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?int $state) => $state ? "{$state} ms" : '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('agent.name')->label('Agente')->toggleable(),
                Tables\Columns\TextColumn::make('latestBackupRun.status')
                    ->label('Ultimo backup')
                    ->badge()
                    ->color(fn ($state) => $state?->color() ?? 'gray')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? 'Sem dados'),
                Tables\Columns\IconColumn::make('is_active')->label('Ativo')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('Tipo')->options([
                    ServerType::WordPress->value  => ServerType::WordPress->label(),
                    ServerType::VpsLaravel->value => ServerType::VpsLaravel->label(),
                    ServerType::Plesk->value      => ServerType::Plesk->label(),
                    ServerType::Cpanel->value     => ServerType::Cpanel->label(),
                ]),
                Tables\Filters\TernaryFilter::make('is_active')->label('Ativo'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('check_now')
                        ->label('Verificar estado')
                        ->icon('heroicon-o-signal')
                        ->color('gray')
                        ->action(function (Server $record) {
                            Artisan::call('server:check', ['--id' => $record->id]);
                            $record->refresh();
                            $label = match ($record->ping_status) {
                                'up'    => 'Online (' . $record->ping_response_ms . ' ms)',
                                'down'  => 'Offline — ' . $record->ping_error,
                                default => 'Desconhecido',
                            };
                            Notification::make()
                                ->title($record->name . ': ' . $label)
                                ->color($record->ping_status === 'up' ? 'success' : 'danger')
                                ->send();
                        }),
                    Tables\Actions\Action::make('ssh_command')
                        ->label('Comandos SSH')
                        ->icon('heroicon-o-command-line')
                        ->color('gray')
                        ->form([
                            Forms\Components\Select::make('preset')
                                ->label('Comando rápido')
                                ->options(collect(SshService::PRESET_COMMANDS)->mapWithKeys(
                                    fn ($v, $k) => [$k => $v['label']]
                                )->toArray())
                                ->live()
                                ->default('disk'),
                            Forms\Components\Textarea::make('custom_command')
                                ->label('Ou escreve um comando')
                                ->placeholder('apt list --upgradable 2>/dev/null')
                                ->helperText('Se preenchido, ignora o comando rápido acima.'),
                        ])
                        ->action(function (Server $record, array $data, SshService $ssh) {
                            try {
                                $command = filled($data['custom_command'])
                                    ? $data['custom_command']
                                    : SshService::PRESET_COMMANDS[$data['preset']]['command'];

                                $result = $ssh->run($record, $command);

                                $output = htmlspecialchars($result['output'] ?: '(sem output)');
                                Notification::make()
                                    ->title('SSH: ' . $record->name)
                                    ->body('<pre style="font-size:0.75rem;white-space:pre-wrap;word-break:break-all;max-height:300px;overflow:auto;background:#111;color:#4ade80;padding:0.75rem;border-radius:0.375rem">' . $output . '</pre>')
                                    ->success()
                                    ->persistent()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erro SSH')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        })
                        ->visible(fn (Server $record) => filled($record->ssh_key_path)),
                    Tables\Actions\Action::make('backup_now')
                        ->label('Fazer backup')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Fazer backup agora')
                        ->modalDescription(fn (Server $record) => "Vai criar um backup de \"{$record->name}\" agora e enviar para o NAS. Pode demorar alguns minutos.")
                        ->visible(fn (Server $record) => $record->is_active && filled($record->ssh_key_path ?? config('backup.ssh_key')))
                        ->action(function (Server $record, BackupService $backup) {
                            $run = $backup->backup($record, 'filament');

                            if ($run->status === BackupStatus::Success) {
                                Notification::make()
                                    ->title('Backup concluído')
                                    ->body("✓ {$record->name} — " . ($run->nas_path ?? 'sem NAS configurado'))
                                    ->success()
                                    ->persistent()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Backup falhou')
                                    ->body($run->error ?? 'Erro desconhecido')
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),
                    Tables\Actions\Action::make('scan_security')
                        ->label('Scan de segurança')
                        ->icon('heroicon-o-shield-exclamation')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Lançar scan de segurança')
                        ->modalDescription(fn (Server $record) => "Vai correr uma análise de segurança em \"{$record->name}\" via SSH. Pode demorar 1-2 minutos.")
                        ->visible(fn (Server $record) => $record->is_active && filled($record->ssh_key_path ?? config('backup.ssh_key')))
                        ->action(function (Server $record, SecurityScanService $scanner) {
                            $scan = $scanner->scan($record, 'filament');

                            $body = match ($scan->status) {
                                SecurityStatus::Clean    => "✓ Nenhum problema encontrado.",
                                SecurityStatus::Warning  => "⚠ {$scan->findings_count} achado(s) — ver relatório para detalhes.",
                                SecurityStatus::Critical => "✗ {$scan->findings_count} achado(s) CRÍTICO(S) — ver relatório imediatamente.",
                                default                  => $scan->error ?? 'Erro desconhecido.',
                            };

                            Notification::make()
                                ->title("Segurança: {$record->name}")
                                ->body($body)
                                ->color($scan->status->color())
                                ->persistent()
                                ->send();
                        }),
                    Tables\Actions\EditAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('backup_selected')
                        ->label('Fazer backup dos seleccionados')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Fazer backup dos servidores seleccionados')
                        ->modalDescription('Vai criar backups em sequência para todos os servidores seleccionados.')
                        ->action(function (Collection $records, BackupService $backup) {
                            $ok     = 0;
                            $failed = 0;

                            foreach ($records as $server) {
                                $run = $backup->backup($server, 'filament');
                                $run->status === BackupStatus::Success ? $ok++ : $failed++;
                            }

                            Notification::make()
                                ->title('Backups concluídos')
                                ->body("✓ {$ok} sucesso   ✗ {$failed} falhou")
                                ->color($failed > 0 ? 'warning' : 'success')
                                ->persistent()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Admin\Resources\ServerResource\RelationManagers\BackupRunsRelationManager::class,
            \App\Filament\Admin\Resources\ServerResource\RelationManagers\SiteMonitorsRelationManager::class,
            \App\Filament\Admin\Resources\ServerResource\RelationManagers\SecurityScansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
        ];
    }
}
