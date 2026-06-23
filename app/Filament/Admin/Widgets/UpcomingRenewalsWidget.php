<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Service;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingRenewalsWidget extends BaseWidget
{
    protected static ?string $heading = 'Renovações próximas (60 dias)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Service::query()
                    ->where('is_active', true)
                    ->where('renewal_date', '<=', now()->addDays(60))
                    ->orderBy('renewal_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Serviço'),
                Tables\Columns\TextColumn::make('domain')
                    ->label('Domínio')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('billing_cycle')
                    ->label('Ciclo')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Valor')
                    ->formatStateUsing(fn (int $state) => number_format($state / 100, 2, ',', '.') . ' €'),
                Tables\Columns\TextColumn::make('renewal_date')
                    ->label('Renovação')
                    ->date('d/m/Y')
                    ->color(fn (Service $record) => match (true) {
                        $record->daysUntilRenewal() < 0  => 'danger',
                        $record->daysUntilRenewal() <= 14 => 'warning',
                        default                           => 'info',
                    })
                    ->badge(),
            ]);
    }
}
