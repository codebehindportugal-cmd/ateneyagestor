<?php

namespace App\Filament\Admin\Resources;

use App\Enums\SecurityStatus;
use App\Enums\ServerEnvironment;
use App\Filament\Admin\Resources\ServerResource\Pages;
use App\Models\Server;
use App\Services\SecurityScanService;
use App\Services\SshService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

/**
 * A MÁQUINA. Só guarda como lá chegar.
 *
 * O que se copia está em SiteResource: o mesmo VPS aloja vários domínios, e
 * cada um pode ser de um cliente e de um tipo diferentes.
 */
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
                    Forms\Components\TextInput::make('label')
                        ->label('Nome da máquina')
                        ->placeholder('Contabo B')
                        ->helperText('Como lhe chamas no dia a dia.'),
                    Forms\Components\TextInput::make('name')
                        ->label('Identificador')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Pasta no NAS — sem espaços, ex: vps-89-117-58-85.'),
                    Forms\Components\Select::make('client_id')
                        ->label('Cliente dono da máquina')
                        ->relationship('client', 'name')
                        ->searchable()
                        ->preload()
                        ->helperText('Só para VPS dedicados. Em branco nas máquinas partilhadas — aí o cliente é de cada site.'),
                    Forms\Components\Select::make('agent_id')
                        ->label('Agente responsavel')
                        ->relationship('agent', 'name')
                        ->searchable()
                        ->preload()
                        ->helperText('Qual agente vai puxar estes backups. Em branco = qualquer agente.'),
                    Forms\Components\Select::make('panel')
                        ->label('Painel instalado')
                        ->options(['plesk' => 'Plesk', 'cpanel' => 'cPanel'])
                        ->placeholder('Nenhum')
                        ->helperText('Se tiver Plesk, os sites desta máquina podem ser copiados com o pleskbackup.'),
                    Forms\Components\Select::make('environment')
                        ->label('Ambiente')
                        ->options([
                            ServerEnvironment::Production->value  => ServerEnvironment::Production->label(),
                            ServerEnvironment::Staging->value     => ServerEnvironment::Staging->label(),
                            ServerEnvironment::Development->value => ServerEnvironment::Development->label(),
                        ])
                        ->default(ServerEnvironment::Production->value)
                        ->required(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Ativo')
                        ->default(true)
                        ->helperText('Desligado, o agente ignora a máquina e todos os seus sites.'),
                ]),

            Forms\Components\Section::make('Ligacao')
                ->description('Usada por todos os sites desta máquina — uma ligação SSH, não uma por domínio.')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('host')->label('Host / IP')->required(),
                    Forms\Components\TextInput::make('port')->label('Porto SSH')->numeric()->default(22),
                    Forms\Components\TextInput::make('user')->label('Utilizador SSH')->default('root')->required(),
                ]),

            Forms\Components\Section::make('Segredos (apenas no agente)')
                ->description('A chave SSH NUNCA é guardada aqui — fica só no secrets.yaml do agente, associada por esta referência.')
                ->schema([
                    Forms\Components\TextInput::make('agent_secret_ref')
                        ->label('Referencia do segredo (agent_secret_ref)')
                        ->helperText('Em branco usa o identificador da máquina. Tem de corresponder a uma entrada em secrets.yaml.'),
                ]),

            Forms\Components\Section::make('Acesso direto SSH (deste painel)')
                ->description('Para correres comandos nesta máquina a partir do painel. A chave fica no teu PC, não na base de dados.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('ssh_key_path')
                        ->label('Caminho da chave SSH privada')
                        ->placeholder('C:/Users/André Mendes/.ssh/id_rsa'),
                    Forms\Components\TextInput::make('plesk_api_key')
                        ->label('Plesk API Key')
                        ->password()
                        ->revealable()
                        ->helperText('Opcional. Criado no Plesk: Ferramentas > API Keys.'),
                ]),

            Forms\Components\Section::make('Retencao por omissao')
                ->description('Aplica-se aos sites desta máquina que não definam a sua própria.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('retention_keep_days')
                        ->label('Manter X dias')->numeric()
                        ->helperText('Em branco = usa o valor global do agente.'),
                    Forms\Components\TextInput::make('retention_keep_min_copies')
                        ->label('Manter sempre no minimo X copias')->numeric(),
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
                Tables\Columns\TextColumn::make('label')
                    ->label('Máquina')
                    ->description(fn (Server $record) => $record->name)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('host')->label('Host')->searchable(),
                Tables\Columns\TextColumn::make('panel')
                    ->label('Painel')
                    ->badge()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('sites_count')
                    ->label('Sites')
                    ->counts('sites')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('environment')
                    ->label('Ambiente')
                    ->badge()
                    ->color(fn (ServerEnvironment $state) => $state->color())
                    ->formatStateUsing(fn (ServerEnvironment $state) => $state->label()),
                Tables\Columns\TextColumn::make('ping_response_ms')
                    ->label('Latência')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?int $state) => $state ? "{$state} ms" : '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('agent.name')->label('Agente')->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->label('Ativo')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('panel')->label('Painel')->options([
                    'plesk'  => 'Plesk',
                    'cpanel' => 'cPanel',
                ]),
                Tables\Filters\SelectFilter::make('environment')->label('Ambiente')->options([
                    ServerEnvironment::Production->value  => ServerEnvironment::Production->label(),
                    ServerEnvironment::Staging->value     => ServerEnvironment::Staging->label(),
                    ServerEnvironment::Development->value => ServerEnvironment::Development->label(),
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
                                ->title(($record->label ?: $record->name) . ': ' . $label)
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
                                    ->title('SSH: ' . ($record->label ?: $record->name))
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
                    Tables\Actions\Action::make('scan_security')
                        ->label('Scan de segurança')
                        ->icon('heroicon-o-shield-exclamation')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Lançar scan de segurança')
                        ->modalDescription(fn (Server $record) => 'Vai correr uma análise de segurança em "' . ($record->label ?: $record->name) . '" via SSH. Pode demorar 1-2 minutos.')
                        ->visible(fn (Server $record) => $record->is_active && filled($record->ssh_key_path ?? config('backup.ssh_key')))
                        ->action(function (Server $record, SecurityScanService $scanner) {
                            $scan = $scanner->scan($record, 'filament');

                            $body = match ($scan->status) {
                                SecurityStatus::Clean    => 'Nenhum problema encontrado.',
                                SecurityStatus::Warning  => "⚠ {$scan->findings_count} achado(s) — ver relatório para detalhes.",
                                SecurityStatus::Critical => "✗ {$scan->findings_count} achado(s) CRÍTICO(S) — ver relatório imediatamente.",
                                default                  => $scan->error ?? 'Erro desconhecido.',
                            };

                            Notification::make()
                                ->title('Segurança: ' . ($record->label ?: $record->name))
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
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Admin\Resources\ServerResource\RelationManagers\SitesRelationManager::class,
            \App\Filament\Admin\Resources\ServerResource\RelationManagers\BackupRunsRelationManager::class,
            \App\Filament\Admin\Resources\ServerResource\RelationManagers\SiteMonitorsRelationManager::class,
            \App\Filament\Admin\Resources\ServerResource\RelationManagers\SecurityScansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'edit'   => Pages\EditServer::route('/{record}/edit'),
        ];
    }
}
