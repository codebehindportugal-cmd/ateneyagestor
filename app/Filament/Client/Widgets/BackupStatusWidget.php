<?php

namespace App\Filament\Client\Widgets;

use App\Enums\BackupStatus;
use App\Models\Server;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class BackupStatusWidget extends BaseWidget
{
    protected static ?string $heading = 'Estado dos teus backups';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Server::query()->where('client_id', Filament::auth()->id()))
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Servidor'),
                Tables\Columns\TextColumn::make('type')->label('Tipo')->badge(),
                Tables\Columns\TextColumn::make('latestBackupRun.status')
                    ->label('Ultimo backup')
                    ->badge()
                    ->state(fn (Server $record) => $record->latestBackupRun()?->status)
                    ->color(fn (?BackupStatus $state) => $state?->color() ?? 'gray')
                    ->formatStateUsing(fn (?BackupStatus $state) => $state?->label() ?? 'Sem dados ainda'),
                Tables\Columns\TextColumn::make('latestBackupRunFinishedAt')
                    ->label('Quando')
                    ->state(fn (Server $record) => $record->latestBackupRun()?->finished_at)
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
            ]);
    }
}
