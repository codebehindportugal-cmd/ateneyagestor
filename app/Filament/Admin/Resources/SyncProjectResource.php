<?php

namespace App\Filament\Admin\Resources;

use App\Enums\SyncStatus;
use App\Filament\Admin\Resources\SyncProjectResource\Pages;
use App\Filament\Admin\Resources\SyncProjectResource\RelationManagers\SyncRunsRelationManager;
use App\Models\SyncProject;
use App\Models\SyncRun;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class SyncProjectResource extends Resource
{
    protected static ?string $model = SyncProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Sincronizadores';

    protected static ?string $modelLabel = 'sincronizador';

    protected static ?string $pluralModelLabel = 'sincronizadores';

    protected static ?string $navigationGroup = 'Integracoes';

    protected static ?int $navigationSort = 1;

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
                        ->live()
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
                    Forms\Components\Select::make('runner_mode')
                        ->label('Onde corre')
                        ->options(SyncProject::runnerModeOptions())
                        ->default('external')
                        ->required()
                        ->helperText('Usa local quando o script vive neste projeto. PHC/Primavera normalmente ficam no cliente.'),
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
            Forms\Components\Section::make('PHC')
                ->description('Chaves e dados de acesso usados pelo sincronizador PHC.')
                ->visible(fn (Get $get) => $get('type') === 'phc_woo')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('phc_base_url')
                        ->label('Servidor SQL / endpoint PHC')
                        ->placeholder('NOME_PC\\SQLEXPRESS'),
                    Forms\Components\TextInput::make('phc_username')
                        ->label('Utilizador SQL / PHC'),
                    Forms\Components\TextInput::make('phc_password')
                        ->label('Password')
                        ->password()
                        ->revealable(),
                    Forms\Components\TextInput::make('phc_database')
                        ->label('Base de dados')
                        ->placeholder('Ex: FClemente_16'),
                    Forms\Components\TextInput::make('phc_company')
                        ->label('Empresa')
                        ->placeholder('Codigo/nome da empresa no PHC'),
                ])
                ->collapsible(),
            Forms\Components\Section::make('WooCommerce')
                ->visible(fn (Get $get) => in_array($get('type'), ['phc_woo', 'wintouch_woo', 'primavera_woo'], true))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('woo_consumer_key')
                        ->label('Consumer key')
                        ->password()
                        ->revealable(),
                    Forms\Components\TextInput::make('woo_consumer_secret')
                        ->label('Consumer secret')
                        ->password()
                        ->revealable(),
                    Forms\Components\TextInput::make('woo_api_version')
                        ->label('Versao API')
                        ->default('wc/v3')
                        ->placeholder('wc/v3'),
                    Forms\Components\TextInput::make('images_base_url')
                        ->label('Base URL das imagens')
                        ->url()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('woo_admin_username')
                        ->label('Utilizador admin WP'),
                    Forms\Components\TextInput::make('woo_admin_app_password')
                        ->label('Application password WP')
                        ->password()
                        ->revealable(),
                ]),
            Forms\Components\Section::make('Wintouch')
                ->visible(fn (Get $get) => $get('type') === 'wintouch_woo')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('wintouch_base_url')
                        ->label('Base URL')
                        ->default('https://api.wintouchcloud.com')
                        ->url(),
                    Forms\Components\TextInput::make('wintouch_api_key')
                        ->label('API key')
                        ->password()
                        ->revealable()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('wintouch_login_email')
                        ->label('Email login')
                        ->email(),
                    Forms\Components\TextInput::make('wintouch_login_password')
                        ->label('Password login')
                        ->password()
                        ->revealable(),
                ]),
            Forms\Components\Section::make('O que sincronizar')
                ->description('Escolhe exatamente que dados este sincronizador pode alterar no destino.')
                ->columns(3)
                ->schema([
                    Forms\Components\Toggle::make('sync_orders')->label('Encomendas')->default(true),
                    Forms\Components\Toggle::make('sync_products')->label('Produtos')->default(true),
                    Forms\Components\Toggle::make('sync_prices')->label('Precos')->default(true),
                    Forms\Components\Toggle::make('sync_images')->label('Imagens')->default(true),
                    Forms\Components\Toggle::make('sync_descriptions')->label('Descricao')->default(true),
                    Forms\Components\Toggle::make('sync_short_descriptions')->label('Breve Descricao')->default(true),
                    Forms\Components\Toggle::make('sync_stock')->label('Stock / disponibilidade')->default(true),
                    Forms\Components\Toggle::make('sync_metadata')->label('Metadados')->default(true),
                    Forms\Components\Hidden::make('sync_download_images')->default(true),
                ]),
            Forms\Components\Section::make('Opcoes da sincronizacao')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('sync_batch_size')
                        ->label('Batch size')
                        ->numeric()
                        ->default(50)
                        ->minValue(1),
                    Forms\Components\TextInput::make('sync_default_currency')
                        ->label('Moeda')
                        ->default('EUR')
                        ->maxLength(3),
                ]),
            Forms\Components\Section::make('Email de relatorio')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('smtp_host')->label('SMTP host'),
                    Forms\Components\TextInput::make('smtp_port')->label('SMTP porta')->numeric()->default(587),
                    Forms\Components\TextInput::make('smtp_user')->label('SMTP user'),
                    Forms\Components\TextInput::make('smtp_password')->label('SMTP password')->password()->revealable(),
                    Forms\Components\TextInput::make('smtp_from')->label('De')->email(),
                    Forms\Components\TextInput::make('smtp_to')->label('Para')->email(),
                ]),
            Forms\Components\Section::make('Codigo do sincronizador')
                ->description('O painel gera automaticamente o pacote do tipo escolhido. Usa este campo so se quiseres substituir por um ZIP/RAR personalizado.')
                ->schema([
                    Forms\Components\FileUpload::make('code_archive_path')
                        ->label('Arquivo personalizado')
                        ->disk('public')
                        ->directory('sync-project-code')
                        ->acceptedFileTypes([
                            'application/zip',
                            'application/x-zip-compressed',
                            'application/vnd.rar',
                            'application/x-rar-compressed',
                            'application/octet-stream',
                        ])
                        ->maxSize(102400)
                        ->downloadable()
                        ->storeFileNamesIn('code_archive_name')
                        ->helperText('Opcional. Se ficar vazio, o download gera o pacote automaticamente para tipos suportados, como PHC.'),
                ])
                ->collapsible(),
            Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('15s')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Tipo')
                    ->formatStateUsing(fn ($state) => SyncProject::typeOptions()[$state] ?? $state)
                    ->badge()->color('info'),
                Tables\Columns\TextColumn::make('site_url')->label('Site')->url(fn ($record) => $record->site_url)->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('client.name')->label('Cliente')->placeholder('-'),
                Tables\Columns\TextColumn::make('runner_mode')
                    ->label('Execução')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => SyncProject::runnerModeOptions()[$state ?? 'external'] ?? $state)
                    ->color(fn (?string $state) => $state === 'local' ? 'success' : 'gray'),
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
                Tables\Columns\TextColumn::make('latestSyncRun.status')
                    ->label('Último run')
                    ->badge()
                    ->formatStateUsing(fn (?SyncStatus $state) => $state?->label() ?? 'Sem run')
                    ->color(fn (?SyncStatus $state) => $state?->color() ?? 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('latestSyncRun.errors_count')
                    ->label('Erros')
                    ->state(fn (SyncProject $record) => $record->latestSyncRun?->errors_count ?? 0)
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'danger' : 'success')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->label('Ativo')->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('run_now')
                    ->label('Correr agora')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Correr sincronizador')
                    ->modalDescription(fn (SyncProject $record) => "Vai executar \"{$record->name}\" agora em segundo plano. O resultado fica no Histórico Sync.")
                    ->action(function (SyncProject $record) {
                        try {
                            if (blank($record->runner_script_path)) {
                                Notification::make()
                                    ->title('Runner não configurado')
                                    ->body('Abre este sincronizador e preenche o caminho do script antes de correr.')
                                    ->warning()
                                    ->persistent()
                                    ->send();

                                return;
                            }

                            $runningRun = SyncRun::query()
                                ->where('sync_project_id', $record->id)
                                ->where('status', SyncStatus::Running)
                                ->where('started_at', '>', now()->subHours(2))
                                ->latest('started_at')
                                ->first();

                            if ($runningRun) {
                                Notification::make()
                                    ->title('Sync ja em curso')
                                    ->body('Ja existe uma execucao ativa desde '.$runningRun->started_at?->format('d/m/Y H:i').'. Consulta o Historico Sync para acompanhar o log.')
                                    ->warning()
                                    ->persistent()
                                    ->send();

                                return;
                            }

                            $run = SyncRun::create([
                                'sync_project_id' => $record->id,
                                'status' => SyncStatus::Running,
                                'started_at' => now(),
                                'log' => '[' . now()->format('H:i:s') . "] Pedido recebido pelo painel. A iniciar processo local...\n",
                            ]);

                            try {
                                self::startSyncProcess($record->slug, $run->id);
                            } catch (\Throwable $e) {
                                $run->update([
                                    'status' => SyncStatus::Failed,
                                    'errors_count' => 1,
                                    'error' => $e->getMessage(),
                                    'finished_at' => now(),
                                ]);

                                throw $e;
                            }

                            Notification::make()
                                ->title('Sync iniciado')
                                ->body('A execução ficou registada em Histórico Sync e vai continuar no servidor.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Erro ao executar sync')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
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
                Tables\Actions\Action::make('downloadClientEnv')
                    ->label('Download config cliente')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (SyncProject $record) {
                        $content = static::clientEnvTemplate($record);
                        $filename = $record->slug . '-cliente.env';

                        return response()->streamDownload(
                            fn () => print($content),
                            $filename,
                            ['Content-Type' => 'text/plain; charset=UTF-8']
                        );
                    }),
                Tables\Actions\Action::make('downloadCodeArchive')
                    ->label('Download codigo')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('gray')
                    ->visible(fn (SyncProject $record) => filled($record->code_archive_path) || static::supportsGeneratedPackage($record))
                    ->action(function (SyncProject $record) {
                        if ($record->code_archive_path) {
                            if (! Storage::disk('public')->exists($record->code_archive_path)) {
                                Notification::make()
                                    ->title('Arquivo nao encontrado')
                                    ->body('Volta a anexar o ZIP/RAR do sincronizador neste registo.')
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                return null;
                            }

                            return Storage::disk('public')->download(
                                $record->code_archive_path,
                                $record->code_archive_name ?: basename($record->code_archive_path)
                            );
                        }

                        try {
                            return static::downloadGeneratedPackage($record);
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Nao foi possivel gerar o pacote')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();

                            return null;
                        }
                    }),
                Tables\Actions\Action::make('viewLatestLog')
                    ->label('Ver log')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('gray')
                    ->visible(fn (SyncProject $record) => filled($record->latestSyncRun))
                    ->modalHeading(fn (SyncProject $record) => 'Último log — '.$record->name)
                    ->modalContent(fn (SyncProject $record) => view('filament.sync-run-details-modal', [
                        'run' => $record->latestSyncRun,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),
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

    private static function startSyncProcess(string $slug, int $runId): void
    {
        $php = self::phpCliPath();
        $artisan = base_path('artisan');
        $logFile = storage_path("logs/sync-{$slug}.log");
        $launcherLogFile = storage_path("logs/sync-{$slug}.launcher.log");
        file_put_contents($launcherLogFile, '[' . now()->format('Y-m-d H:i:s') . "] Launcher iniciado para {$slug} com {$php}.\n");

        if (PHP_OS_FAMILY === 'Windows') {
            $errorLogFile = storage_path("logs/sync-{$slug}.error.log");
            file_put_contents($errorLogFile, '');
            $command = 'cmd /C start "" /MIN '
                . self::windowsArg($php) . ' '
                . self::windowsArg($artisan) . ' sync:run '
                . self::windowsArg($slug) . ' --run-id='
                . self::windowsArg((string) $runId) . ' > '
                . self::windowsArg($logFile) . ' 2> '
                . self::windowsArg($errorLogFile);
        } else {
            $command = 'nohup '
                . escapeshellarg($php) . ' '
                . escapeshellarg($artisan) . ' sync:run '
                . escapeshellarg($slug) . ' --run-id='
                . escapeshellarg((string) $runId) . ' > '
                . escapeshellarg($logFile) . ' 2>&1 &';
        }

        $handle = @popen($command, 'r');

        if (! is_resource($handle)) {
            throw new \RuntimeException('Não foi possível iniciar o processo do sincronizador.');
        }

        $exitCode = pclose($handle);

        if ($exitCode !== 0) {
            throw new \RuntimeException("Falha ao iniciar o processo do sincronizador (exit code {$exitCode}).");
        }

        file_put_contents($launcherLogFile, '[' . now()->format('Y-m-d H:i:s') . "] Processo entregue ao sistema.\n", FILE_APPEND);
    }

    private static function phpCliPath(): string
    {
        // PHP_BINARY on production points outside open_basedir (e.g. /opt/plesk/php/...),
        // so is_file() on it triggers an open_basedir warning. It comes straight from the
        // running SAPI, so it's already known-good — use it directly without validating.
        if (PHP_OS_FAMILY !== 'Windows') {
            $binary = PHP_BINARY ?: 'php';

            // When this action runs from a web request, PHP-FPM serves it and
            // PHP_BINARY points to the php-fpm binary, not the CLI one.
            if (str_contains($binary, 'php-fpm')) {
                $binary = str_replace('/sbin/php-fpm', '/bin/php', $binary);
            }

            return $binary;
        }

        $candidate = 'C:\laragon\bin\php\php-8.2.5-Win32-vs16-x64\php.exe';
        if (is_file($candidate)) {
            return $candidate;
        }

        return PHP_BINARY ?: 'php.exe';
    }

    private static function clientEnvTemplate(SyncProject $project, ?string $backupManagerToken = null): string
    {
        if ($project->type === 'phc_woo') {
            return static::phcClientEnvTemplate($project, $backupManagerToken);
        }

        $lines = [
            '# Backup Manager',
            'BACKUP_MANAGER_URL=' . rtrim((string) config('app.url'), '/'),
            'BACKUP_MANAGER_TOKEN=' . ($backupManagerToken ?: 'GERAR_TOKEN_NO_PAINEL'),
            'SYNC_PROJECT_SLUG=' . $project->slug,
            '',
            '# WooCommerce',
            'WC_URL=' . ($project->site_url ?: 'https://loja-do-cliente.pt'),
            'WC_CONSUMER_KEY=' . ($project->woo_consumer_key ?: 'ck_xxxxxxxxx'),
            'WC_CONSUMER_SECRET=' . ($project->woo_consumer_secret ?: 'cs_xxxxxxxxx'),
            'WC_API_VERSION=' . ($project->woo_api_version ?: 'wc/v3'),
            '',
        ];

        if ($project->type === 'wintouch_woo') {
            $lines = array_merge($lines, [
                '# Wintouch',
                'WINTOUCH_BASE_URL=' . ($project->wintouch_base_url ?: 'https://api.wintouchcloud.com'),
                'WINTOUCH_API_KEY=' . ($project->wintouch_api_key ?: ''),
                'WINTOUCH_LOGIN_EMAIL=' . ($project->wintouch_login_email ?: ''),
                'WINTOUCH_LOGIN_PASSWORD=' . ($project->wintouch_login_password ?: ''),
                '',
            ]);
        }

        $lines = array_merge($lines, [
            '# Caminhos locais',
            'LOG_DIR=logs',
            'EXPORT_DIR=exports',
            'IMAGE_DIR=imagens',
            '',
            '# Sincronizacao',
            'BATCH_SIZE=' . ($project->sync_batch_size ?: 50),
            'DEFAULT_CURRENCY=' . ($project->sync_default_currency ?: 'EUR'),
            'DOWNLOAD_IMAGES=' . (($project->sync_images ?? $project->sync_download_images) ? 'true' : 'false'),
            'SYNC_ORDERS=' . (($project->sync_orders ?? true) ? 'true' : 'false'),
            'SYNC_PRODUCTS=' . (($project->sync_products ?? true) ? 'true' : 'false'),
            'SYNC_PRICES=' . (($project->sync_prices ?? true) ? 'true' : 'false'),
            'SYNC_IMAGES=' . (($project->sync_images ?? $project->sync_download_images) ? 'true' : 'false'),
            'SYNC_DESCRIPTIONS=' . (($project->sync_descriptions ?? true) ? 'true' : 'false'),
            'SYNC_SHORT_DESCRIPTIONS=' . (($project->sync_short_descriptions ?? true) ? 'true' : 'false'),
            'SYNC_STOCK=' . (($project->sync_stock ?? true) ? 'true' : 'false'),
            'SYNC_METADATA=' . (($project->sync_metadata ?? true) ? 'true' : 'false'),
            '',
        ]);

        return implode("\n", $lines);
    }

    private static function phcClientEnvTemplate(SyncProject $project, ?string $backupManagerToken = null): string
    {
        $lines = [
            '# SQL Server (PHC)',
            'SQL_SERVER=' . ($project->phc_base_url ?: 'NOME_PC\\SQLEXPRESS'),
            'SQL_DATABASE=' . ($project->phc_database ?: 'NOME_DA_BASE'),
            'SQL_USER=' . ($project->phc_username ?: 'phc_sync_user'),
            'SQL_PASSWORD=' . ($project->phc_password ?: ''),
            '',
            '# WooCommerce',
            'WC_URL=' . ($project->site_url ?: 'https://loja-do-cliente.pt'),
            'WC_CONSUMER_KEY=' . ($project->woo_consumer_key ?: 'ck_xxxxxxxxx'),
            'WC_CONSUMER_SECRET=' . ($project->woo_consumer_secret ?: 'cs_xxxxxxxxx'),
            '',
            '# Email para envio dos logs',
            'SMTP_HOST=' . ($project->smtp_host ?: 'smtp-pt.securemail.pro'),
            'SMTP_PORT=' . ($project->smtp_port ?: 587),
            'SMTP_USER=' . ($project->smtp_user ?: ''),
            'SMTP_PASS=' . ($project->smtp_password ?: ''),
            'EMAIL_TO=' . ($project->smtp_to ?: $project->smtp_user),
            '',
            '# Backup Manager',
            'BACKUP_MANAGER_URL=' . rtrim((string) config('app.url'), '/'),
            'BACKUP_MANAGER_TOKEN=' . ($backupManagerToken ?: 'GERAR_TOKEN_NO_PAINEL'),
            'SYNC_PROJECT_SLUG=' . $project->slug,
            '',
        ];

        return implode("\n", $lines);
    }

    public static function supportsGeneratedPackage(SyncProject $project): bool
    {
        return $project->type === 'phc_woo';
    }

    public static function downloadGeneratedPackage(SyncProject $project)
    {
        $templateDir = static::generatedPackageSourceDir($project);

        if (! is_dir($templateDir)) {
            throw new \RuntimeException('Codigo do sincronizador nao encontrado: '.$templateDir);
        }

        $tmpDir = storage_path('app/generated-sync-packages');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $filename = $project->slug . '-codigo.zip';
        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . $filename;

        $project->tokens()->where('name', 'sync_package')->delete();
        $backupManagerToken = $project->createToken('sync_package')->plainTextToken;

        $entries = static::packageEntries($templateDir);
        $entries['.env'] = static::clientEnvTemplate($project, $backupManagerToken);
        $entries['config.generated.json'] = json_encode($project->toRunnerConfig(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $entries['run-sync.bat'] ??= "@echo off\r\ncd /d \"%~dp0\"\r\npython main.py\r\npause\r\n";

        static::writeZip($zipPath, $entries);

        return response()
            ->download($zipPath, $filename, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    private static function generatedPackageSourceDir(SyncProject $project): string
    {
        if ($project->type === 'phc_woo') {
            $laragonProject = dirname(base_path()) . DIRECTORY_SEPARATOR . 'phc_woo_sync';

            if (is_dir($laragonProject)) {
                return $laragonProject;
            }
        }

        return base_path('syncer/phc_woo');
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
        $excludedDirs = ['.git', '.venv', 'venv', '__pycache__', 'dist', 'build'];
        $excludedFiles = ['.env', 'sync.log'];

        foreach ($segments as $segment) {
            if (in_array($segment, $excludedDirs, true)) {
                return true;
            }
        }

        return in_array(basename($path), $excludedFiles, true)
            || str_ends_with($path, '.pyc')
            || str_ends_with($path, '.pyo')
            || str_ends_with($path, '.log');
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
            ) . $name;

            $zip .= $localHeader . $contents;

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
            ) . $name;

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
            throw new \RuntimeException('Nao foi possivel gravar o ZIP do sincronizador.');
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

    private static function windowsArg(string $value): string
    {
        return '"' . str_replace('"', '\"', $value) . '"';
    }
}
