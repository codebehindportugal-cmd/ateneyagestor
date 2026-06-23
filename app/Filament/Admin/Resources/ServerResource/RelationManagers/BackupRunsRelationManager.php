<?php

namespace App\Filament\Admin\Resources\ServerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BackupRunsRelationManager extends RelationManager
{
    protected static string $relationship = 'backupRuns';

    protected static ?string $title = 'Historico de backups';

    public function canCreate(): bool
    {
        // Runs are only ever created by the agent API, never by hand here.
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn ($record) => $record->status?->color())
                    ->formatStateUsing(fn ($record) => $record->status?->label()),
                Tables\Columns\TextColumn::make('started_at')->label('Inicio')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('finished_at')->label('Fim')->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('error')->label('Erro')->limit(60)->wrap()->toggleable(),
            ])
            ->defaultSort('started_at', 'desc');
    }
}
