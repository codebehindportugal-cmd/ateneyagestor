<?php

namespace App\Filament\Admin\Resources;

use App\Enums\BackupStatus;
use App\Filament\Admin\Resources\BackupRunResource\Pages;
use App\Models\BackupRun;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only history of every backup run reported by every agent. Rows are
 * only ever created by AgentController::storeRunResults() -- there is no
 * create/edit form here on purpose.
 */
class BackupRunResource extends Resource
{
    protected static ?string $model = BackupRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Historico de backups';

    protected static ?string $modelLabel = 'execucao de backup';

    protected static ?string $pluralModelLabel = 'execucoes de backup';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server.name')->label('Servidor')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('server.client.name')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('agent.name')->label('Agente')->toggleable(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn (BackupStatus $state) => $state->color())
                    ->formatStateUsing(fn (BackupStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('started_at')->label('Inicio')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('finished_at')->label('Fim')->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('size_bytes')
                    ->label('Tamanho')
                    ->formatStateUsing(fn ($state) => $state ? (
                        $state >= 1048576
                            ? round($state / 1048576, 1) . ' MB'
                            : round($state / 1024, 1) . ' KB'
                    ) : '-')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('nas_path')->label('NAS')->limit(50)->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('triggered_by')->label('Origem')->badge()
                    ->color(fn ($state) => match ($state) {
                        'filament' => 'info',
                        'command'  => 'gray',
                        default    => 'gray',
                    })->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('error')->label('Erro')->limit(60)->wrap()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')->options([
                    BackupStatus::Success->value => BackupStatus::Success->label(),
                    BackupStatus::Failed->value  => BackupStatus::Failed->label(),
                    BackupStatus::Running->value => BackupStatus::Running->label(),
                ]),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('started_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBackupRuns::route('/'),
        ];
    }
}
