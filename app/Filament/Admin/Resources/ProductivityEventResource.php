<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProductivityEventResource\Pages;
use App\Models\ProductivityEvent;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductivityEventResource extends Resource
{
    protected static ?string $model = ProductivityEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Produtividade';

    protected static ?string $modelLabel = 'evento de produtividade';

    protected static ?string $pluralModelLabel = 'eventos de produtividade';

    protected static ?string $navigationGroup = 'Operação';

    protected static ?int $navigationSort = 20;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('agent.name')
                    ->label('Agente')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('hostname')
                    ->label('Computador')
                    ->searchable(),
                Tables\Columns\TextColumn::make('event_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'app' => 'info',
                        'site' => 'warning',
                        'active' => 'success',
                        'idle' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('app_name')
                    ->label('App')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('process_name')
                    ->label('Processo')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('domain')
                    ->label('Dominio')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('activity_state')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (?string $state) => $state === 'idle' ? 'warning' : 'success')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('duration_seconds')
                    ->label('Duracao')
                    ->formatStateUsing(fn (int $state) => gmdate('H:i:s', $state))
                    ->alignEnd()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('agent_id')
                    ->label('Agente')
                    ->relationship('agent', 'name'),
                Tables\Filters\SelectFilter::make('event_type')
                    ->label('Tipo')
                    ->options([
                        'app' => 'Aplicacao',
                        'site' => 'Site',
                        'active' => 'Ativo',
                        'idle' => 'Inativo',
                        'heartbeat' => 'Heartbeat',
                    ]),
                Tables\Filters\SelectFilter::make('activity_state')
                    ->label('Estado')
                    ->options([
                        'active' => 'Ativo',
                        'idle' => 'Inativo',
                    ]),
            ])
            ->defaultSort('started_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductivityEvents::route('/'),
        ];
    }
}
