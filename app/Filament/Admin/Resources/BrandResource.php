<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BrandResource\Pages;
use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon  = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Marcas';
    protected static ?string $modelLabel      = 'marca';
    protected static ?string $pluralModelLabel = 'marcas';
    protected static ?string $navigationGroup = 'Clientes';
    protected static ?int    $navigationSort  = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificação')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nome da marca')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\Select::make('parent_brand_id')
                        ->label('Empresa mãe')
                        ->relationship('parent', 'name')
                        ->searchable()
                        ->preload()
                        ->placeholder('Nenhuma (marca raiz)')
                        ->helperText('Deixa em branco se for uma empresa/marca de topo.'),

                    Forms\Components\ColorPicker::make('color')
                        ->label('Cor')
                        ->helperText('Usada para identificação visual nos painéis.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Activa')
                        ->default(true),
                ]),

            Forms\Components\Section::make('Logo')
                ->schema([
                    Forms\Components\FileUpload::make('logo_path')
                        ->label('Logo (opcional)')
                        ->disk('public')
                        ->directory('brand-logos')
                        ->image()
                        ->imagePreviewHeight('80')
                        ->maxSize(2048)
                        ->helperText('PNG ou SVG, máx. 2 MB.'),
                ])
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('')
                    ->disk('public')
                    ->height(32)
                    ->width(32)
                    ->defaultImageUrl(null)
                    ->circular(),

                Tables\Columns\ColorColumn::make('color')
                    ->label('Cor'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Marca')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Empresa mãe')
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('clients_count')
                    ->label('Clientes')
                    ->counts('clients'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Activa'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit'   => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
