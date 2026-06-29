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
                        ->label('Finalidade / descrição')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Texto curto para o contabilista perceber para que e a fatura.')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('tipo')
                        ->label('Tipo de documento')
                        ->options(AccountingDocument::tipos())
                        ->default('fatura')
                        ->required(),

                    Forms\Components\Select::make('estado')
                        ->label('Estado')
                        ->options(AccountingDocument::estados())
                        ->default('pendente')
                        ->required(),

                    Forms\Components\DatePicker::make('date')
                        ->label('Data')
                        ->required()
                        ->default(now())
                        ->displayFormat('d/m/Y'),

                    Forms\Components\TextInput::make('invoice_number')
                        ->label('Número de documento')
                        ->maxLength(100)
                        ->placeholder('Ex: FT 2024/001')
                        ->hint('Preenchido automaticamente pelo QR code'),

                    Forms\Components\TextInput::make('fornecedor')
                        ->label('Fornecedor')
                        ->maxLength(255)
                        ->placeholder('Nome do fornecedor'),

                    Forms\Components\TextInput::make('supplier_nif')
                        ->label('NIF do Emitente')
                        ->maxLength(20)
                        ->placeholder('Ex: 500000000')
                        ->hint('NIF extraído do QR code AT'),

                    Forms\Components\TextInput::make('atcud')
                        ->label('ATCUD')
                        ->maxLength(100)
                        ->placeholder('Ex: 0:12345')
                        ->hint('Código ATCUD do QR code AT'),
                ]),

            Forms\Components\Section::make('Valor & Categoria')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('brand_id')
                        ->label('Marca / Empresa')
                        ->options(fn () => Brand::selectOptions())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Obrigatorio para o contabilista saber a que marca da Ateneya pertence.')
                        ->placeholder('Geral (sem marca específica)')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('amount_cents')
                        ->label('Total (c/ IVA)')
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

                    Forms\Components\TextInput::make('iva_cents')
                        ->label('Total IVA')
                        ->numeric()
                        ->prefix('€')
                        ->minValue(0)
                        ->step(0.01)
                        ->afterStateHydrated(
                            fn (Forms\Components\TextInput $c, $state) =>
                                $c->state($state !== null ? number_format($state / 100, 2, '.', '') : null)
                        )
                        ->dehydrateStateUsing(fn ($state) => (int) round((float) $state * 100))
                        ->hint('IVA extraído do QR code AT'),

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
                        ->label('PDF da fatura')
                        ->disk('public')
                        ->directory('accounting-documents')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(20480)
                        ->downloadable()
                        ->openable()
                        ->storeFileNamesIn('file_name')
                        ->helperText('Máximo 20 MB — PDF, JPG, PNG ou WebP.'),
                ]),

            Forms\Components\Section::make('Imagens')
                ->schema([
                    Forms\Components\FileUpload::make('image_paths')
                        ->label('Fotos/imagens da fatura')
                        ->disk('public')
                        ->directory('accounting-document-images')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                        ->multiple()
                        ->reorderable()
                        ->downloadable()
                        ->openable()
                        ->storeFileNamesIn('image_names')
                        ->maxSize(20480)
                        ->helperText('Podes guardar varias fotos/screenhots da fatura.'),
                ])
                ->collapsed(false),

            Forms\Components\Section::make('Produtos')
                ->schema([
                    Forms\Components\Repeater::make('products')
                        ->label('Lista de produtos')
                        ->schema([
                            Forms\Components\TextInput::make('description')
                                ->label('Descrição')
                                ->required()
                                ->columnSpan(4),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Qtd.')
                                ->numeric()
                                ->default(1)
                                ->minValue(0)
                                ->step(0.01),
                            Forms\Components\TextInput::make('unitPrice')
                                ->label('Preço un.')
                                ->numeric()
                                ->prefix('€')
                                ->minValue(0)
                                ->step(0.01),
                            Forms\Components\TextInput::make('vatRate')
                                ->label('IVA %')
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01),
                            Forms\Components\TextInput::make('lineTotal')
                                ->label('Total linha')
                                ->numeric()
                                ->prefix('€')
                                ->minValue(0)
                                ->step(0.01),
                            Forms\Components\TextInput::make('confidence')
                                ->label('Conf.')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(1)
                                ->step(0.01)
                                ->helperText('0 a 1'),
                        ])
                        ->columns(9)
                        ->defaultItems(0)
                        ->addActionLabel('Adicionar produto')
                        ->reorderable()
                        ->collapsible()
                        ->columnSpanFull(),
                ])
                ->collapsed(false),

            Forms\Components\Section::make('Notas')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Notas para o contabilista')
                        ->rows(3)
                        ->placeholder('Ex: compra para obra X, software da loja, material para stock...'),
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

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => AccountingDocument::tipos()[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'fatura'       => 'info',
                        'recibo'       => 'success',
                        'nota_credito' => 'warning',
                        default        => 'gray',
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Finalidade')
                    ->searchable()
                    ->limit(35),

                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Nº Doc.')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('fornecedor')
                    ->label('Fornecedor')
                    ->searchable()
                    ->placeholder('-')
                    ->limit(25)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('supplier_nif')
                    ->label('NIF Emitente')
                    ->searchable()
                    ->placeholder('-')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => AccountingDocument::estados()[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'pendente'  => 'warning',
                        'aprovado'  => 'info',
                        'pago'      => 'success',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => AccountingDocument::categories()[$state] ?? $state)
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Total')
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

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Produtos')
                    ->state(fn (AccountingDocument $record) => count($record->products ?? []))
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('images_count')
                    ->label('Imagens')
                    ->state(fn (AccountingDocument $record) => count($record->image_paths ?? []))
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'gray')
                    ->toggleable(),
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

                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(AccountingDocument::tipos()),

                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(AccountingDocument::estados()),

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
