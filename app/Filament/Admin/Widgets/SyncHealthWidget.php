<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\SyncStatus;
use App\Models\SyncProject;
use App\Models\SyncRun;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SyncHealthWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $activeProjects = SyncProject::where('is_active', true)->count();
        $runningRuns = SyncRun::where('status', SyncStatus::Running)
            ->where('started_at', '>', now()->subHours(2))
            ->count();
        $failedToday = SyncRun::where('status', SyncStatus::Failed)
            ->where('started_at', '>=', now()->startOfDay())
            ->count();
        $lastRun = SyncRun::with('syncProject')->latest('started_at')->first();

        return [
            Stat::make('Sincronizadores ativos', $activeProjects)
                ->description('Projetos prontos a reportar ou correr')
                ->icon('heroicon-o-arrows-right-left')
                ->color('info'),

            Stat::make('Execuções em curso', $runningRuns)
                ->description($runningRuns > 0 ? 'A acompanhar no histórico sync' : 'Nenhum processo ativo')
                ->icon('heroicon-o-bolt')
                ->color($runningRuns > 0 ? 'warning' : 'success'),

            Stat::make('Falhas hoje', $failedToday)
                ->description($failedToday > 0 ? 'Ver Logs & Diagnóstico' : 'Sem falhas registadas hoje')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($failedToday > 0 ? 'danger' : 'success'),

            Stat::make('Último sync', $lastRun?->syncProject?->name ?? 'Sem execuções')
                ->description($lastRun ? $lastRun->status->label().' · '.$lastRun->started_at?->diffForHumans() : 'Ainda sem histórico')
                ->icon('heroicon-o-clock')
                ->color($lastRun?->status->color() ?? 'gray'),
        ];
    }
}
