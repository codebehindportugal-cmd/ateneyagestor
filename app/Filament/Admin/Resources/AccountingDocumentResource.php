<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AccountingDocumentResource\Pages;
use App\Models\AccountingDocument;
use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class AccountingDocumentResource extends Resource
{
    protected static ?string $model = AccountingDocument::class;

    protected static ?string $navigationIcon  = 'heroicon-o-archive-box-arrow-down';
    protected static ?string $navigationLabel = 'Documentos';
    protected static ?string $modelLabel      = 'documento';
    protected static ?string $pluralModelLabel = 'documentos';
    protected static ?string $navigationGroup = 'Contabilidade';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\View::make('filament.forms.components.qr-scanner')
                ->columnSpan('full'),

            Forms\Components\Section::make('Identificação')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Título / Descrição')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('invoice_number')
                        ->label('Número de fatura')
                        ->maxLength(100)
                        ->placeholder('Ex: FT 2024/001')
                        ->hint('Preenchido automaticamente pelo QR code'),

                    Forms\Components\TextInput::make('supplier_nif')
                        ->label('NIF do Emitente')
                        ->maxLength(20)
                        ->placeholder('Ex: 500000000')
                        ->hint('NIF extraído do QR code AT'),

                    Forms\Components\DatePicker::make('date')
                        ->label('Data')
                        ->required()
                        ->default(now())
                        ->displayFormat('d/m/Y'),
                ]),

            Forms\Components\Section::make('Valor & Categoria')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('brand_id')
                        ->label('Marca / Empresa')
                        ->options(fn () => Brand::selectOptions())
                        ->searchable()
                        ->placeholder('Geral (sem marca específica)')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('amount_cents')
                        ->label('Valor (EUR)')
                        ->numeric()
                        ->prefix('€')
                        ->required()
                        ->minValue(0)
                        ->step(0.01)
                        ->afterStateHydrated(
                            fn (Forms\Components\TextInput $c, $state) =>
                                $c->state($state !== null ? number_format($state / 100, 2, '.', '') : null)
                        )
                        ->dehydrateStateUsing(fn ($state) => (int) round((float) $state * 100)),

                    Forms\Components\Select::make('category')
                        ->label('Categoria')
                        ->options(AccountingDocument::categories())
                        ->default('outros')
                        ->required(),

                    Forms\Components\Select::make('currency')
                        ->label('Moeda')
                        ->options(['EUR' => 'EUR €', 'USD' => 'USD $', 'GBP' => 'GBP £'])
                        ->default('EUR')
                        ->required(),
                ]),

            Forms\Components\Section::make('Ficheiro')
                ->schema([
                    Forms\Components\FileUpload::make('file_path')
                        ->label('PDF ou imagem')
                        ->disk('public')
                        ->directory('accounting-documents')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                        ->maxSize(20480)
                        ->downloadable()
                        ->openable()
                        ->storeFileNamesIn('file_name')
                        ->helperText('Máximo 20 MB — PDF, JPG, PNG ou WebP.'),
                ]),

            Forms\Components\Section::make('Notas')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Notas internas')
                        ->rows(3)
                        ->placeholder('Observações adicionais para o contabilista...'),
                ])
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Nº Fatura')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('supplier_nif')
                    ->label('NIF Emitente')
                    ->searchable()
                    ->placeholder('-')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => AccountingDocument::categories()[$state] ?? $state)
                    ->color('info'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->money('EUR')
                    ->sortable('amount_cents')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Marca')
                    ->badge()
                    ->color('primary')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('file_path')
                    ->label('Ficheiro')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('Ano')
                    ->options(function () {
                        return AccountingDocument::query()
                            ->selectRaw('DISTINCT year')
                            ->orderByDesc('year')
                            ->pluck('year', 'year')
                            ->toArray();
                    }),

                Tables\Filters\SelectFilter::make('month')
                    ->label('Mês')
                    ->options(function () {
                        $months = [];
                        for ($i = 1; $i <= 12; $i++) {
                            $months[$i] = AccountingDocument::monthName($i);
                        }
                        return $months;
                    }),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(AccountingDocument::categories()),

                Tables\Filters\SelectFilter::make('brand_id')
                    ->label('Marca')
                    ->options(fn () => Brand::selectOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn (AccountingDocument $record) => filled($record->file_path))
                    ->url(fn (AccountingDocument $record) => Storage::disk('public')->url($record->file_path))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAccountingDocuments::route('/'),
            'create' => Pages\CreateAccountingDocument::route('/create'),
            'edit'   => Pages\EditAccountingDocument::route('/{record}/edit'),
        ];
    }
}
