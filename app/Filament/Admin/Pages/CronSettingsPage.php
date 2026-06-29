<?php

namespace App\Filament\Admin\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

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
        return 'Agendamentos e tarefas automáticas';
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('Monitorização')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('site_monitor_enabled')->label('Validar sites online')->default(true),
                        Forms\Components\TextInput::make('site_monitor_cron')->label('Cron')->required()->helperText('Ex: */5 * * * *'),
                        Forms\Components\Toggle::make('server_check_enabled')->label('Verificar servidores')->default(true),
                        Forms\Components\TextInput::make('server_check_cron')->label('Cron')->required()->helperText('Ex: */5 * * * *'),
                    ]),
                Forms\Components\Section::make('Backups e segurança')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('backup_enabled')->label('Backups para NAS')->default(true),
                        Forms\Components\TextInput::make('backup_cron')->label('Cron')->required()->helperText('Ex: 0 3 * * *'),
                        Forms\Components\Toggle::make('security_scan_enabled')->label('Scans de segurança')->default(true),
                        Forms\Components\TextInput::make('security_scan_cron')->label('Cron')->required()->helperText('Ex: 0 4 * * 1'),
                    ]),
                Forms\Components\Section::make('Atualizações')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('updates_enabled')->label('Verificar updates')->default(false),
                        Forms\Components\TextInput::make('updates_cron')->label('Cron')->required()->helperText('Ex: 0 5 * * 1'),
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
                ->action(fn () => $this->runCommand('monitor:sites', 'Validação de sites concluída')),
            Action::make('run_servers')
                ->label('Ver servidores agora')
                ->icon('heroicon-o-server-stack')
                ->color('gray')
                ->action(fn () => $this->runCommand('server:check', 'Verificação de servidores concluída')),
            Action::make('run_backups')
                ->label('Backup agora')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('info')
                ->requiresConfirmation()
                ->action(fn () => $this->runCommand('backup:run', 'Backup concluído', ['--all' => true])),
            Action::make('run_security')
                ->label('Scan agora')
                ->icon('heroicon-o-shield-exclamation')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn () => $this->runCommand('security:scan', 'Scan concluído', ['--all' => true])),
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
            ->body('O scheduler do Laravel passa a usar estes valores no próximo minuto.')
            ->success()
            ->send();
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
}
