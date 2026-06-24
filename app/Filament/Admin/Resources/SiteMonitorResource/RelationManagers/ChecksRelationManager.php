<?php

namespace App\Filament\Admin\Resources\SiteMonitorResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ChecksRelationManager extends RelationManager
{
    protected static string $relationship = 'checks';

    protected static ?string $title = 'Histórico de verificações';

    public function canCreate(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('checked_at')
            ->defaultSort('checked_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => $state === 'up' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state) => strtoupper($state)),
                Tables\Columns\TextColumn::make('checked_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('http_code')
                    ->label('HTTP')
                    ->placeholder('—')
                    ->badge()
                    ->color(fn (?int $state) => match (true) {
                        $state === null => 'gray',
                        $state < 300   => 'success',
                        $state < 400   => 'info',
                        default        => 'danger',
                    }),
                Tables\Columns\TextColumn::make('response_ms')
                    ->label('Tempo')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?int $state) => $state ? "{$state} ms" : '—'),
                Tables\Columns\TextColumn::make('error')
                    ->label('Erro')
                    ->placeholder('—')
                    ->limit(60)
                    ->toggleable(),
            ]);
    }
}
