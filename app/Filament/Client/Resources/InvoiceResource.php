<?php

namespace App\Filament\Client\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\Client\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only for the client: they can see their own invoices and whether
 * they're paid/outstanding, but cannot create, edit, or delete anything
 * here -- this is bookkeeping managed from the Admin panel only.
 */
class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pré-faturas';

    protected static ?string $modelLabel = 'pré-fatura';

    protected static ?string $pluralModelLabel = 'pré-faturas';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('client_id', Filament::auth()->id());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('Número'),
                Tables\Columns\TextColumn::make('brand.full_name')
                    ->label('Marca')
                    ->badge()
                    ->color('primary')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('hours')->label('Horas')->suffix('h')->placeholder('-'),
                Tables\Columns\TextColumn::make('amount')->label('Valor')->money('EUR'),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn (InvoiceStatus $state) => $state->color())
                    ->formatStateUsing(fn (InvoiceStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('issued_at')->label('Emitida em')->date('d/m/Y')->placeholder('-'),
                Tables\Columns\TextColumn::make('due_at')->label('Vencimento')->date('d/m/Y')->placeholder('-'),
                Tables\Columns\TextColumn::make('paid_at')->label('Pago em')->date('d/m/Y')->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
        ];
    }
}
