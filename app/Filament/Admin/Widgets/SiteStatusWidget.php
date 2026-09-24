<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\MonitorStatus;
use App\Models\SiteMonitor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiteStatusWidget extends BaseWidget
{
    /** Só o administrador. Um estagiário nem vê isto no menu. */
    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() === true;
    }

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $total   = SiteMonitor::where('is_active', true)->count();
        $up      = SiteMonitor::where('is_active', true)->where('status', MonitorStatus::Up)->count();
        $down    = SiteMonitor::where('is_active', true)->where('status', MonitorStatus::Down)->count();
        $unknown = SiteMonitor::where('is_active', true)->where('status', MonitorStatus::Unknown)->count();

        $downSites = SiteMonitor::where('is_active', true)
            ->where('status', MonitorStatus::Down)
            ->with('client')
            ->get()
            ->map(fn ($m) => $m->name)
            ->join(', ');

        // Lento = média das respostas das últimas 24 h acima de 2 s.
        $lentos = \App\Filament\Admin\Resources\SiteMonitorResource::comEstatisticas24h(
            SiteMonitor::query()->where('is_active', true)
        )->get()->filter(fn ($m) => $m->media_24h !== null && $m->media_24h >= 2000);

        return [
            Stat::make('Sites lentos (média 24h > 2 s)', $lentos->count())
                ->description($lentos->isNotEmpty()
                    ? $lentos->map(fn ($m) => $m->name . ' ' . number_format($m->media_24h / 1000, 1, ',', '') . ' s')->join(', ')
                    : 'Todos abaixo de 2 s')
                ->icon('heroicon-o-clock')
                ->color($lentos->isNotEmpty() ? 'warning' : 'success'),

            Stat::make('Sites online', $up . ' / ' . $total)
                ->icon('heroicon-o-signal')
                ->color($down > 0 ? 'danger' : 'success'),

            Stat::make('Sites offline', $down)
                ->description($down > 0 ? $downSites : 'Tudo a funcionar')
                ->icon('heroicon-o-x-circle')
                ->color($down > 0 ? 'danger' : 'success'),

            Stat::make('Desconhecidos', $unknown)
                ->description($unknown > 0 ? 'Corre monitor:sites para verificar' : 'Todos verificados')
                ->icon('heroicon-o-question-mark-circle')
                ->color($unknown > 0 ? 'warning' : 'gray'),
        ];
    }
}
