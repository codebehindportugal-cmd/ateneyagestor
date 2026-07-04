<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use App\Support\PhpCli;
use Cron\CronExpression;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class CronSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Agendamentos';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.cron-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'site_monitor_enabled' => Setting::bool('cron.site_monitor.enabled', true),
            'site_monitor_cron' => Setting::get('cron.site_monitor.cron', '*/5 * * * *'),
            'server_check_enabled' => Setting::bool('cron.server_check.enabled', true),
            'server_check_cron' => Setting::get('cron.server_check.cron', '*/5 * * * *'),
            'backup_enabled' => Setting::bool('cron.backup.enabled', true),
            'backup_cron' => Setting::get('cron.backup.cron', '0 3 * * *'),
            'security_scan_enabled' => Setting::bool('cron.security_scan.enabled', true),
            'security_scan_cron' => Setting::get('cron.security_scan.cron', '0 4 * * 1'),
            'updates_enabled' => Setting::bool('cron.updates.enabled', false),
            'updates_cron' => Setting::get('cron.updates.cron', '0 5 * * 1'),
        ]);
    }

    public function getTitle(): string
    {
        return 'Agendamentos e tarefas automaticas';
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('Monitorizacao')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('site_monitor_enabled')->label('Validar sites online')->default(true),
                        $this->cronInput('site_monitor_cron'),
                        Forms\Components\Toggle::make('server_check_enabled')->label('Verificar servidores')->default(true),
                        $this->cronInput('server_check_cron'),
                    ]),
                Forms\Components\Section::make('Backups e seguranca')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('backup_enabled')->label('Backups para NAS')->default(true),
                        $this->cronInput('backup_cron'),
                        Forms\Components\Toggle::make('security_scan_enabled')->label('Scans de seguranca')->default(true),
                        $this->cronInput('security_scan_cron'),
                    ]),
                Forms\Components\Section::make('Updates')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('updates_enabled')->label('Verificar updates')->default(false),
                        $this->cronInput('updates_cron'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar agendamentos')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('save'),
            Action::make('run_monitor')
                ->label('Validar sites agora')
                ->icon('heroicon-o-signal')
                ->color('gray')
                ->action(fn () => $this->runCommand('monitor:sites', 'Validacao de sites concluida')),
            Action::make('run_servers')
                ->label('Ver servidores agora')
                ->icon('heroicon-o-server-stack')
                ->color('gray')
                ->action(fn () => $this->runCommand('server:check', 'Verificacao de servidores concluida')),
            Action::make('run_backups')
                ->label('Backup agora')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('info')
                ->requiresConfirmation()
                ->action(fn () => $this->runArtisanInBackground(
                    'backup:run --all',
                    'backup-manual',
                    'Backup iniciado em background',
                    'Percorre todos os servidores ativos e pode demorar vários minutos. Consulta storage/logs/backup-manual.log ou o histórico de backups para acompanhar.'
                )),
            Action::make('run_security')
                ->label('Scan agora')
                ->icon('heroicon-o-shield-exclamation')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn () => $this->runArtisanInBackground(
                    'security:scan --all',
                    'security-scan-manual',
                    'Scan iniciado em background',
                    'Percorre todos os servidores ativos e pode demorar vários minutos. Consulta storage/logs/security-scan-manual.log ou o histórico de scans para acompanhar.'
                )),
            Action::make('run_updates')
                ->label('Ver updates agora')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('runUpdates'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $map = [
            'site_monitor_enabled' => 'cron.site_monitor.enabled',
            'site_monitor_cron' => 'cron.site_monitor.cron',
            'server_check_enabled' => 'cron.server_check.enabled',
            'server_check_cron' => 'cron.server_check.cron',
            'backup_enabled' => 'cron.backup.enabled',
            'backup_cron' => 'cron.backup.cron',
            'security_scan_enabled' => 'cron.security_scan.enabled',
            'security_scan_cron' => 'cron.security_scan.cron',
            'updates_enabled' => 'cron.updates.enabled',
            'updates_cron' => 'cron.updates.cron',
        ];

        foreach ($map as $field => $key) {
            $value = $state[$field] ?? null;
            Setting::set($key, is_bool($value) ? ($value ? '1' : '0') : $value);
        }

        Notification::make()
            ->title('Agendamentos guardados')
            ->body('O scheduler do Laravel passa a usar estes valores no proximo minuto.')
            ->success()
            ->send();
    }

    public function runUpdates(): void
    {
        $this->runShellInBackground(
            'composer outdated --direct',
            'update-check',
            'Verificacao de updates iniciada em background',
            'Consulta storage/logs/update-check.log dentro de alguns segundos para o resultado.'
        );
    }

    private function runCommand(string $command, string $successMessage, array $parameters = []): void
    {
        $exitCode = Artisan::call($command, $parameters);
        $output = trim(Artisan::output());

        Notification::make()
            ->title($exitCode === 0 ? $successMessage : 'A tarefa terminou com erro')
            ->body($output !== '' ? $output : null)
            ->color($exitCode === 0 ? 'success' : 'danger')
            ->persistent()
            ->send();
    }

    /**
     * Runs an artisan command as a detached, non-blocking process so the
     * PHP-FPM worker handling this request isn't held for the whole duration
     * (backup:run/security:scan --all can take many minutes across servers,
     * which was timing out nginx with a 504 on this page).
     */
    private function runArtisanInBackground(string $commandLine, string $logName, string $title, string $body): void
    {
        $shellCommand = sprintf(
            '%s %s %s',
            escapeshellarg(PhpCli::path()),
            escapeshellarg(base_path('artisan')),
            $commandLine
        );

        $this->runShellInBackground($shellCommand, $logName, $title, $body);
    }

    private function runShellInBackground(string $shellCommand, string $logName, string $title, string $body): void
    {
        $env = null;
        if (PHP_OS_FAMILY !== 'Windows') {
            $env = ['PATH' => PhpCli::binDir() . ':' . (getenv('PATH') ?: '/usr/bin:/bin')];
        }

        $logFile = storage_path("logs/{$logName}.log");

        $process = Process::fromShellCommandline(
            sprintf('%s >> %s 2>&1', $shellCommand, escapeshellarg($logFile)),
            base_path(),
            $env
        );
        $process->setTimeout(null);
        $process->disableOutput();
        // create_new_console stops Process::__destruct() from sending SIGTERM
        // once this method returns and $process is garbage collected — without
        // it the "background" run would be killed right after the request ends.
        $process->setOptions(['create_new_console' => true]);
        $process->start();

        Notification::make()
            ->title($title)
            ->body($body)
            ->success()
            ->send();
    }

    private function cronInput(string $name): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make($name)
            ->label('Cron')
            ->required()
            ->helperText('Ex: */5 * * * *')
            ->rule(function () {
                return function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! CronExpression::isValidExpression((string) $value)) {
                        $fail('Expressao cron invalida.');
                    }
                };
            });
    }
}
