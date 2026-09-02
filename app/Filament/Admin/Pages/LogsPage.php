<?php

namespace App\Filament\Admin\Pages;

use App\Models\Agent;
use App\Models\BackupRun;
use App\Models\SyncProject;
use App\Models\SyncRun;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class LogsPage extends Page
{
    /** Só o administrador. Um estagiário nem vê isto no menu. */
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() === true;
    }

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
                ->label('Atualizar')
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
            'backupAgents' => $this->getBackupAgents(),
            'backupErrors' => $this->getRecentBackupErrors(),
            'syncErrors'   => $this->getRecentSyncErrors(),
            'syncRuns'     => $this->getRecentSyncRuns(),
            'syncProjects' => $this->getSyncProjects(),
            'logFiles'     => $this->getLogFiles(),
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

    /**
     * Agentes de backup e o veredicto da última corrida de cada um.
     * Um agente "online" que falhou 12 de 14 backups continua a parecer
     * saudável em qualquer outra vista — é para isso que isto existe.
     */
    private function getBackupAgents(): \Illuminate\Database\Eloquent\Collection
    {
        return Agent::where('agent_type', '!=', 'productivity')
            ->orWhereNull('agent_type')
            ->orderBy('name')
            ->get();
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

    private function getRecentSyncRuns(): \Illuminate\Database\Eloquent\Collection
    {
        return SyncRun::with('syncProject')
            ->orderByDesc('started_at')
            ->limit(30)
            ->get();
    }

    private function getSyncProjects(): \Illuminate\Database\Eloquent\Collection
    {
        return SyncProject::with('latestSyncRun')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function getLogFiles(): array
    {
        $paths = array_merge(
            glob(storage_path('logs/*.log')) ?: [],
            glob(base_path('syncer/*/logs/*.log')) ?: [],
            glob(dirname(base_path()).DIRECTORY_SEPARATOR.'phc_woo_sync'.DIRECTORY_SEPARATOR.'*.log') ?: [],
            glob(dirname(base_path()).DIRECTORY_SEPARATOR.'phc_woo_sync'.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'*.log') ?: [],
        );

        return collect($paths)
            ->filter(fn (string $path) => is_file($path))
            ->unique()
            ->map(fn (string $path) => [
                'name' => basename($path),
                'path' => $path,
                'size' => filesize($path) ?: 0,
                'updated_at' => filemtime($path) ? now()->createFromTimestamp(filemtime($path)) : null,
                'tail' => $this->tailFile($path),
            ])
            ->sortByDesc(fn (array $file) => $file['updated_at']?->timestamp ?? 0)
            ->values()
            ->all();
    }

    private function tailFile(string $path): string
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return '(não foi possível ler o ficheiro)';
        }

        return substr($contents, -12000);
    }
}
