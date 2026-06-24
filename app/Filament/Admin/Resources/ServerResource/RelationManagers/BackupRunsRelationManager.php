<?php

namespace App\Filament\Admin\Resources\ServerResource\RelationManagers;

use App\Enums\BackupStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BackupRunsRelationManager extends RelationManager
{
    protected static string $relationship = 'backupRuns';

    protected static ?string $title = 'Historico de backups';

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
                    ->color(fn (BackupStatus $state) => $state->color())
                    ->formatStateUsing(fn (BackupStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Início')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Fim')
                    ->dateTime('H:i'),
                Tables\Columns\TextColumn::make('size_bytes')
                    ->label('Tamanho')
                    ->formatStateUsing(fn ($state) => $state ? (
                        $state >= 1048576
                            ? round($state / 1048576, 1) . ' MB'
                            : round($state / 1024, 1) . ' KB'
                    ) : '-')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('file_count')
                    ->label('Ficheiros')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('nas_path')
                    ->label('Caminho NAS')
                    ->limit(60)
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('triggered_by')
                    ->label('Origem')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'filament' => 'info',
                        'command'  => 'gray',
                        default    => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('error')
                    ->label('Erro')
                    ->limit(80)
                    ->wrap()
                    ->color('danger')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->defaultSort('started_at', 'desc');
    }
}
