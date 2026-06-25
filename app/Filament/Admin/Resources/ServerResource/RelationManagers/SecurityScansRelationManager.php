<?php

namespace App\Filament\Admin\Resources\ServerResource\RelationManagers;

use App\Enums\SecurityStatus;
use App\Models\SecurityScan;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SecurityScansRelationManager extends RelationManager
{
    protected static string $relationship = 'securityScans';

    protected static ?string $title = 'Scans de segurança';

    public function canCreate(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (SecurityStatus $state) => $state->color())
                    ->formatStateUsing(fn (SecurityStatus $state) => $state->label())
                    ->icon(fn (SecurityStatus $state) => $state->icon()),
                Tables\Columns\TextColumn::make('findings_count')
                    ->label('Achados')
                    ->placeholder('0'),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Iniciado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Duração')
                    ->formatStateUsing(function (SecurityScan $record) {
                        $secs = $record->durationSeconds();
                        return $secs !== null ? "{$secs}s" : '—';
                    })
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('triggered_by')
                    ->label('Origem')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'filament' => 'info',
                        'command'  => 'gray',
                        default    => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver relatório')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->url(fn (SecurityScan $record) => route(
                        'filament.admin.resources.security-scans.view',
                        ['record' => $record]
                    )),
            ])
            ->defaultSort('started_at', 'desc');
    }
}
