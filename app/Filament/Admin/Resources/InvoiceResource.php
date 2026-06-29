<?php

namespace App\Filament\Admin\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\Admin\Resources\InvoiceResource\Pages;
use App\Models\Brand;
use App\Models\Client;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pré-faturas';

    protected static ?string $modelLabel = 'pré-fatura';

    protected static ?string $pluralModelLabel = 'pré-faturas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificação')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('client_id')
                        ->label('Cliente')
                        ->relationship('client', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            if (filled($get('brand_id'))) {
                                return;
                            }

                            $set('brand_id', Client::find($state)?->brand_id);
                        })
                        ->required(),
                    Forms\Components\Select::make('brand_id')
                        ->label('Marca / Empresa')
                        ->options(fn () => Brand::selectOptions())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Indica a marca Ateneya a que esta fatura pertence.'),
                    Forms\Components\TextInput::make('number')
                        ->label('Número')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->default(fn () => 'PFAT-'.now()->format('Y').'-'.Str::upper(Str::random(4))),
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options(array_combine(
                            array_map(fn ($c) => $c->value, InvoiceStatus::cases()),
                            array_map(fn ($c) => $c->label(), InvoiceStatus::cases()),
                        ))
                        ->default(InvoiceStatus::Draft->value)
                        ->required(),
                    Forms\Components\DatePicker::make('issued_at')->label('Data de emissão'),
                    Forms\Components\DatePicker::make('due_at')->label('Data de vencimento'),
                    Forms\Components\DatePicker::make('paid_at')
                        ->label('Data de pagamento')
                        ->helperText('Preenche para marcar como paga.'),
                ]),

            Forms\Components\Section::make('Trabalho realizado')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('hours')
                        ->label('Horas')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.25)
                        ->suffix('h')
                        ->placeholder('0.00'),
                    Forms\Components\TextInput::make('amount_cents')
                        ->label('Valor total (EUR)')
                        ->numeric()
                        ->prefix('EUR')
                        ->required()
                        ->afterStateHydrated(fn (Forms\Components\TextInput $component, $state) => $component->state($state !== null ? $state / 100 : null))
                        ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                    Forms\Components\Textarea::make('description')
                        ->label('Detalhes das tarefas realizadas')
                        ->placeholder('Ex: Configuração do servidor, migração da base de dados, suporte...')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('Número')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.name')->label('Cliente')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('brand.full_name')
                    ->label('Marca')
                    ->badge()
                    ->color('primary')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('hours')->label('Horas')->suffix('h')->placeholder('-')->sortable(),
                Tables\Columns\TextColumn::make('amount')->label('Valor')->money('EUR')->sortable('amount_cents'),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn (InvoiceStatus $state) => $state->color())
                    ->formatStateUsing(fn (InvoiceStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('issued_at')->label('Emitida')->date('d/m/Y')->sortable()->placeholder('-'),
                Tables\Columns\TextColumn::make('paid_at')->label('Pago em')->date('d/m/Y')->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')->options(array_combine(
                    array_map(fn ($c) => $c->value, InvoiceStatus::cases()),
                    array_map(fn ($c) => $c->label(), InvoiceStatus::cases()),
                )),
                Tables\Filters\SelectFilter::make('brand_id')
                    ->label('Marca')
                    ->options(fn () => Brand::selectOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('markPaid')
                    ->label('Marcar como paga')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Invoice $record) => $record->status !== InvoiceStatus::Paid)
                    ->requiresConfirmation()
                    ->action(fn (Invoice $record) => $record->markPaid()),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
