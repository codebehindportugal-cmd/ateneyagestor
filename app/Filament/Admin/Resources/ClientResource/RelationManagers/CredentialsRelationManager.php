<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Models\Credential;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CredentialsRelationManager extends RelationManager
{
    protected static string $relationship = 'credentials';

    protected static ?string $title = 'Cofre de Passwords';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category')
                ->label('Categoria')
                ->options(Credential::categoryOptions())
                ->required()
                ->default('other'),
            Forms\Components\TextInput::make('label')
                ->label('Descrição / rótulo')
                ->required()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('url')
                ->label('URL / Host')
                ->nullable(),
            Forms\Components\TextInput::make('username')
                ->label('Utilizador')
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
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
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
                Tables\Columns\TextColumn::make('username')
                    ->label('Utilizador')
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Copiado!'),
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
                    ->limit(30),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
