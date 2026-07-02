<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CredentialResource\Pages;
use App\Models\Credential;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CredentialResource extends Resource
{
    protected static ?string $model = Credential::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Cofre de Passwords';

    protected static ?string $modelLabel = 'credencial';

    protected static ?string $pluralModelLabel = 'credenciais';

    protected static ?string $navigationGroup = 'Infraestrutura';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\Select::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\Select::make('category')
                    ->label('Categoria')
                    ->options(Credential::categoryOptions())
                    ->required()
                    ->default('other'),
                Forms\Components\TextInput::make('label')
                    ->label('Descrição / rótulo')
                    ->required()
                    ->placeholder('Ex: Plesk Contabo A — root')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('url')
                    ->label('URL / Host')
                    ->placeholder('https://89.117.58.229:8443')
                    ->url()
                    ->nullable(),
                Forms\Components\TextInput::make('username')
                    ->label('Utilizador')
                    ->placeholder('root')
                    ->nullable(),
                Forms\Components\TextInput::make('password')
                    ->label('Password / Chave')
                    ->password()
                    ->revealable()
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notas')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('client.name')
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Credential::categoryOptions()[$state] ?? $state)
                    ->color(fn (string $state) => Credential::categoryColors()[$state] ?? 'gray'),
                Tables\Columns\TextColumn::make('label')
                    ->label('Descrição')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->placeholder('(interno)')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('Utilizador')
                    ->placeholder('—')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Utilizador copiado!'),
                Tables\Columns\TextColumn::make('password')
                    ->label('Password')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => filled($state) ? '••••••••' : null)
                    ->copyable()
                    ->copyMessage('Password copiada!')
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->placeholder('—')
                    ->url(fn (?string $state) => $state)
                    ->openUrlInNewTab()
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(Credential::categoryOptions()),
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->searchable()
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCredentials::route('/'),
            'create' => Pages\CreateCredential::route('/create'),
            'edit'   => Pages\EditCredential::route('/{record}/edit'),
        ];
    }
}
