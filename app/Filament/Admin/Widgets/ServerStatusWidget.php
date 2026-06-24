<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Server;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ServerStatusWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $total   = Server::where('is_active', true)->count();
        $up      = Server::where('is_active', true)->where('ping_status', 'up')->count();
        $down    = Server::where('is_active', true)->where('ping_status', 'down')->count();
        $unknown = Server::where('is_active', true)->where('ping_status', 'unknown')->count();

        $downNames = Server::where('is_active', true)
            ->where('ping_status', 'down')
            ->pluck('name')
            ->join(', ');

        return [
            Stat::make('Servidores online', $up . ' / ' . $total)
                ->icon('heroicon-o-server-stack')
                ->color($down > 0 ? 'danger' : 'success'),

            Stat::make('Servidores offline', $down)
                ->description($down > 0 ? $downNames : 'Todos acessíveis')
                ->icon('heroicon-o-x-circle')
                ->color($down > 0 ? 'danger' : 'success'),

            Stat::make('Sem verificação', $unknown)
                ->description($unknown > 0 ? 'Aguarda próxima verificação (5 min)' : 'Todos verificados')
                ->icon('heroicon-o-question-mark-circle')
                ->color($unknown > 0 ? 'warning' : 'gray'),
        ];
    }
}
