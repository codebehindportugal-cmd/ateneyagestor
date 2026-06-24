<?php

namespace App\Filament\Admin\Resources\SyncProjectResource\RelationManagers;

use App\Enums\SyncStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SyncRunsRelationManager extends RelationManager
{
    protected static string $relationship = 'syncRuns';

    protected static ?string $title = 'Histórico de execuções';

    public function canCreate(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn (SyncStatus $state) => $state->color())
                    ->formatStateUsing(fn (SyncStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('products_synced')->label('Produtos')->sortable(),
                Tables\Columns\TextColumn::make('orders_synced')->label('Encomendas')->sortable(),
                Tables\Columns\TextColumn::make('errors_count')->label('Erros')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('started_at')->label('Início')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('finished_at')->label('Fim')->dateTime('d/m/Y H:i'),
            ])
            ->actions([
                Tables\Actions\Action::make('viewDetails')
                    ->label('Detalhes')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('gray')
                    ->modalContent(fn ($record) => view('filament.sync-run-details-modal', ['run' => $record]))
                    ->modalHeading(fn ($record) => 'Detalhes — '.$record->started_at?->format('d/m/Y H:i'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),
            ])
            ->defaultSort('started_at', 'desc');
    }
}
