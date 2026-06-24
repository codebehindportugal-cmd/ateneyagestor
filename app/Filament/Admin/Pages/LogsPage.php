<?php

namespace App\Filament\Admin\Pages;

use App\Models\BackupRun;
use App\Models\SyncRun;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class LogsPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-document-magnifying-glass';
    protected static ?string $navigationLabel = 'Logs & Diagnóstico';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?int    $navigationSort  = 99;
    protected static string  $view            = 'filament.pages.logs-page';

    public string $activeTab = 'laravel';

    public function getTitle(): string
    {
        return 'Logs & Diagnóstico';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualizar')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => null),
            Action::make('run_schedule')
                ->label('Forçar scheduler')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->action(function () {
                    Artisan::call('schedule:run');
                }),
        ];
    }

    public function getViewData(): array
    {
        return [
            'laravelLog'   => $this->readLaravelLog(),
            'scheduleList' => $this->getScheduleList(),
            'backupErrors' => $this->getRecentBackupErrors(),
            'syncErrors'   => $this->getRecentSyncErrors(),
        ];
    }

    private function readLaravelLog(): string
    {
        $today    = now()->format('Y-m-d');
        $paths    = [
            storage_path("logs/laravel-{$today}.log"),
            storage_path('logs/laravel.log'),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                // Return last 200 lines
                $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                $last  = array_slice($lines, -200);
                return implode("\n", array_reverse($last));
            }
        }

        return '(sem log disponível)';
    }

    private function getScheduleList(): string
    {
        Artisan::call('schedule:list');
        return Artisan::output();
    }

    private function getRecentBackupErrors(): \Illuminate\Database\Eloquent\Collection
    {
        return BackupRun::with('server')
            ->where('status', 'failed')
            ->orderByDesc('started_at')
            ->limit(20)
            ->get();
    }

    private function getRecentSyncErrors(): \Illuminate\Database\Eloquent\Collection
    {
        return SyncRun::with('syncProject')
            ->whereIn('status', ['failed', 'running'])
            ->orderByDesc('started_at')
            ->limit(20)
            ->get();
    }
}
