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
use Illuminate\Support\Str;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'Agentes';

    protected static ?string $modelLabel = 'agente';

    protected static ?string $pluralModelLabel = 'agentes';

    protected static ?string $navigationGroup = 'Operação';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificacao')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('agent_type')
                        ->label('Tipo')
                        ->options([
                            'productivity' => 'Computador - produtividade',
                            'backup' => 'Agente de backups',
                        ])
                        ->default('productivity')
                        ->required()
                        ->live(),
                    Forms\Components\TextInput::make('name')
                        ->label('Nome interno')
                        ->required()
                        ->helperText('Ex: PC Rececao, PC Andre, Portatil Comercial.'),
                    Forms\Components\TextInput::make('slug')->label('Identificador')->disabled()->dehydrated(false),
                ]),
            Forms\Components\Section::make('Computador da empresa')
                ->description('Dados usados para gerar o instalador e identificar claramente que computador esta a comunicar.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('computer_name')
                        ->label('Nome do computador')
                        ->helperText('Opcional. Se ficar vazio, o monitor envia o nome real do Windows.'),
                    Forms\Components\TextInput::make('assigned_user_name')
                        ->label('Pessoa que utiliza')
                        ->required(fn (Forms\Get $get) => $get('agent_type') === 'productivity'),
                    Forms\Components\TextInput::make('assigned_user_email')
                        ->label('Email da pessoa')
                        ->email(),
                    Forms\Components\TextInput::make('department')
                        ->label('Departamento / loja'),
                    Forms\Components\TextInput::make('asset_tag')
                        ->label('Codigo do equipamento')
                        ->helperText('Ex: etiqueta patrimonial, serial interno ou codigo Ateneya.'),
                ])
                ->visible(fn (Forms\Get $get) => $get('agent_type') === 'productivity'),
            Forms\Components\Section::make('Politica de produtividade')
                ->description('O instalador gerado fica com estes dados preenchidos. Recolhe apenas apps e atividade/inatividade.')
                ->columns(3)
                ->schema([
                    Forms\Components\Toggle::make('productivity_monitor_enabled')
                        ->label('Ativo')
                        ->default(true)
                        ->inline(false),
                    Forms\Components\TextInput::make('productivity_send_interval_seconds')
                        ->label('Enviar a cada (segundos)')
                        ->numeric()
                        ->required()
                        ->default(60),
                    Forms\Components\TextInput::make('productivity_sample_interval_seconds')
                        ->label('Verificar a cada (segundos)')
                        ->numeric()
                        ->required()
                        ->default(5),
                    Forms\Components\TextInput::make('productivity_idle_threshold_seconds')
                        ->label('Inativo apos (segundos)')
                        ->numeric()
                        ->required()
                        ->default(300),
                    Forms\Components\Toggle::make('productivity_work_hours_enabled')
                        ->label('So horario laboral')
                        ->default(true)
                        ->inline(false)
                        ->live(),
                    Forms\Components\TextInput::make('productivity_work_start')
                        ->label('Inicio')
                        ->default('09:00')
                        ->required()
                        ->visible(fn (Forms\Get $get) => (bool) $get('productivity_work_hours_enabled')),
                    Forms\Components\TextInput::make('productivity_work_end')
                        ->label('Fim')
                        ->default('18:00')
                        ->required()
                        ->visible(fn (Forms\Get $get) => (bool) $get('productivity_work_hours_enabled')),
                    Forms\Components\CheckboxList::make('productivity_work_weekdays')
                        ->label('Dias')
                        ->options([
                            1 => 'Seg',
                            2 => 'Ter',
                            3 => 'Qua',
                            4 => 'Qui',
                            5 => 'Sex',
                            6 => 'Sab',
                            7 => 'Dom',
                        ])
                        ->default([1, 2, 3, 4, 5])
                        ->columns(7)
                        ->columnSpanFull()
                        ->visible(fn (Forms\Get $get) => (bool) $get('productivity_work_hours_enabled')),
                    Forms\Components\Toggle::make('productivity_collect_domains')
                        ->label('Recolher dominios dos sites')
                        ->helperText('Guarda apenas o dominio visitado, sem URL completo, titulo, conteudo, mensagens, passwords, screenshots ou teclas.')
                        ->default(true)
                        ->inline(false),
                ])
                ->visible(fn (Forms\Get $get) => $get('agent_type') === 'productivity'),
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
                ])
                ->visible(fn (Forms\Get $get) => $get('agent_type') === 'backup'),
            Forms\Components\Section::make('Monitor de produtividade')
                ->description('Depois de guardar, usa o botao "Download instalador" para descarregar o ZIP ja com chave API, URL e politica preenchidas.')
                ->columns(2)
                ->schema([
                    Forms\Components\Placeholder::make('privacy_notice')
                        ->label('Privacidade')
                        ->content('Computador da empresa: monitorizacao transparente e proporcional. Nao recolhe teclas, screenshots, passwords, mensagens, conteudo de paginas ou ficheiros pessoais.'),
                    Forms\Components\Placeholder::make('install_notice')
                        ->label('Instalacao')
                        ->content('O instalador fica ligado a este computador/agente. A plataforma mostra ultimo contacto e eventos recebidos para verificacao.'),
                ])
                ->visible(fn (Forms\Get $get) => $get('agent_type') === 'productivity'),
            Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Computador / agente')
                    ->description(fn (Agent $record) => collect([
                        $record->computer_name,
                        $record->assigned_user_name,
                    ])->filter()->join(' - '))
                    ->searchable(),
                Tables\Columns\TextColumn::make('agent_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state === 'productivity' ? 'Produtividade' : 'Backups')
                    ->color(fn (?string $state) => $state === 'productivity' ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn (string $state) => $state === 'online' ? 'success' : 'gray')
                    ->formatStateUsing(fn (string $state) => $state === 'online' ? 'Online' : 'Offline'),
                Tables\Columns\TextColumn::make('last_seen_at')->label('Ultimo contacto')->dateTime('d/m/Y H:i')->placeholder('Nunca'),
                Tables\Columns\TextColumn::make('productivity_events_count')
                    ->label('Eventos')
                    ->counts('productivityEvents')
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('department')->label('Departamento')->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Actions\Action::make('downloadProductivityMonitor')
                    ->label('Download instalador')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (Agent $record) => $record->agent_type === 'productivity')
                    ->requiresConfirmation()
                    ->modalHeading('Gerar instalador do monitor')
                    ->modalDescription('Vai gerar uma chave API nova para este computador e criar um ZIP pronto a instalar. A monitorizacao e transparente e limitada a apps/atividade.')
                    ->action(fn (Agent $record) => static::downloadProductivityMonitorPackage($record)),
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

    public static function productivityInstallerAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('downloadProductivityMonitor')
            ->label('Download instalador')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->visible(fn (Agent $record) => $record->agent_type === 'productivity')
            ->requiresConfirmation()
            ->modalHeading('Gerar instalador do monitor')
            ->modalDescription('Vai gerar uma chave API nova para este computador e criar um ZIP pronto a instalar.')
            ->action(fn (Agent $record) => static::downloadProductivityMonitorPackage($record));
    }

    private static function downloadProductivityMonitorPackage(Agent $agent)
    {
        $sourceDir = base_path('agents/productivity_monitor');

        if (! is_dir($sourceDir)) {
            Notification::make()
                ->title('Codigo do monitor nao encontrado')
                ->body('A pasta agents/productivity_monitor nao existe.')
                ->danger()
                ->persistent()
                ->send();

            return null;
        }

        if (! $agent->productivity_monitor_enabled) {
            Notification::make()
                ->title('Monitor desativado')
                ->body('Ativa o monitor de produtividade neste agente antes de gerar o instalador.')
                ->warning()
                ->send();

            return null;
        }

        $agent->tokens()->where('name', 'productivity_monitor')->delete();
        $token = $agent->createToken('productivity_monitor')->plainTextToken;

        $deviceUid = $agent->slug.'-'.Str::lower(Str::random(8));

        $config = [
            'api_url' => rtrim((string) config('app.url'), '/'),
            'token' => $token,
            'device_uid' => $deviceUid,
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'computer_name' => $agent->computer_name,
                'assigned_user_name' => $agent->assigned_user_name,
                'assigned_user_email' => $agent->assigned_user_email,
                'department' => $agent->department,
                'asset_tag' => $agent->asset_tag,
            ],
            'send_interval_seconds' => (int) $agent->productivity_send_interval_seconds,
            'sample_interval_seconds' => (int) $agent->productivity_sample_interval_seconds,
            'idle_threshold_seconds' => (int) $agent->productivity_idle_threshold_seconds,
            'work_hours' => [
                'enabled' => (bool) $agent->productivity_work_hours_enabled,
                'start' => $agent->productivity_work_start ?: '09:00',
                'end' => $agent->productivity_work_end ?: '18:00',
                'weekdays' => array_map('intval', $agent->productivity_work_weekdays ?: [1, 2, 3, 4, 5]),
            ],
            'privacy' => [
                'collect_window_titles' => false,
                'collect_domains' => (bool) $agent->productivity_collect_domains,
                'collect_screenshots' => false,
                'collect_keystrokes' => false,
            ],
        ];

        $entries = static::packageEntries($sourceDir);
        $entries['config.json'] = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        unset($entries['config.example.json']);

        $tmpDir = storage_path('app/generated-agent-packages');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $filename = $agent->slug.'-monitor-produtividade.zip';
        $zipPath = $tmpDir.DIRECTORY_SEPARATOR.$filename;

        static::writeZip($zipPath, $entries);

        return response()
            ->download($zipPath, $filename, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    private static function packageEntries(string $directory): array
    {
        $entries = [];
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($items as $item) {
            $relativePath = ltrim(str_replace('\\', '/', substr($item->getPathname(), strlen($directory))), '/');

            if (static::shouldExcludeFromPackage($relativePath)) {
                continue;
            }

            $entries[$relativePath] = file_get_contents($item->getPathname()) ?: '';
        }

        return $entries;
    }

    private static function shouldExcludeFromPackage(string $relativePath): bool
    {
        $path = trim(str_replace('\\', '/', $relativePath), '/');
        $segments = explode('/', $path);

        foreach ($segments as $segment) {
            if (in_array($segment, ['__pycache__', '.venv', 'venv', '.git'], true)) {
                return true;
            }
        }

        return str_ends_with($path, '.pyc')
            || str_ends_with($path, '.pyo')
            || basename($path) === 'config.json'
            || basename($path) === 'device_uid.txt';
    }

    private static function writeZip(string $path, array $entries): void
    {
        $zip = '';
        $centralDirectory = '';
        $offset = 0;

        foreach ($entries as $name => $contents) {
            $name = str_replace('\\', '/', $name);
            $contents = (string) $contents;
            $crc = crc32($contents);
            $size = strlen($contents);
            [$dosTime, $dosDate] = static::dosTimestamp();

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                0,
                $dosTime,
                $dosDate,
                $crc,
                $size,
                $size,
                strlen($name),
                0
            ).$name;

            $zip .= $localHeader.$contents;

            $centralDirectory .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                0,
                $dosTime,
                $dosDate,
                $crc,
                $size,
                $size,
                strlen($name),
                0,
                0,
                0,
                0,
                32,
                $offset
            ).$name;

            $offset += strlen($localHeader) + $size;
        }

        $zip .= $centralDirectory;
        $zip .= pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            count($entries),
            count($entries),
            strlen($centralDirectory),
            $offset,
            0
        );

        if (file_put_contents($path, $zip) === false) {
            throw new \RuntimeException('Nao foi possivel gravar o ZIP do monitor.');
        }
    }

    private static function dosTimestamp(): array
    {
        $time = getdate();

        $dosTime = (($time['hours'] & 0x1f) << 11)
            | (($time['minutes'] & 0x3f) << 5)
            | (intdiv($time['seconds'], 2) & 0x1f);

        $dosDate = ((($time['year'] - 1980) & 0x7f) << 9)
            | (($time['mon'] & 0x0f) << 5)
            | ($time['mday'] & 0x1f);

        return [(int) $dosTime, (int) $dosDate];
    }
}
