<?php

namespace App\Filament\Admin\Resources;

use App\Enums\SyncStatus;
use App\Filament\Admin\Resources\SyncRunResource\Pages;
use App\Models\SyncRun;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SyncRunResource extends Resource
{
    protected static ?string $model = SyncRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Histórico Sync';

    protected static ?string $modelLabel = 'execução';

    protected static ?string $pluralModelLabel = 'execuções';

    protected static ?string $navigationGroup = 'Integrações';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('syncProject.name')->label('Projeto')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn (SyncStatus $state) => $state->color())
                    ->formatStateUsing(fn (SyncStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('products_synced')->label('Produtos')->sortable(),
                Tables\Columns\TextColumn::make('orders_synced')->label('Encomendas')->sortable(),
                Tables\Columns\TextColumn::make('errors_count')->label('Erros')
                    ->color(fn ($state) => $state > 0 ? 'danger' : null),
                Tables\Columns\TextColumn::make('started_at')->label('Início')
                    ->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('finished_at')->label('Duração')
                    ->formatStateUsing(fn ($state, $record) => $record->durationSeconds() !== null
                        ? gmdate('H:i:s', $record->durationSeconds())
                        : '-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sync_project_id')
                    ->label('Projeto')
                    ->relationship('syncProject', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(array_combine(
                        array_map(fn ($c) => $c->value, SyncStatus::cases()),
                        array_map(fn ($c) => $c->label(), SyncStatus::cases()),
                    )),
            ])
            ->actions([
                Tables\Actions\Action::make('viewLog')
                    ->label('Log')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->modalContent(fn ($record) => view('filament.sync-log-modal', ['log' => $record->log]))
                    ->modalHeading(fn ($record) => ($record->syncProject->name ?? 'Sync').' — '.$record->started_at?->format('d/m/Y H:i'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->visible(fn ($record) => ! empty($record->log)),
            ])
            ->defaultSort('started_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSyncRuns::route('/'),
        ];
    }
}
