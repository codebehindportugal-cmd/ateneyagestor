<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ClientResource\Pages;
use App\Filament\Admin\Resources\ClientResource\RelationManagers\CredentialsRelationManager;
use App\Filament\Admin\Resources\ClientResource\RelationManagers\ServicesRelationManager;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'clientes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados do cliente')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')->label('Nome')->required(),
                    Forms\Components\TextInput::make('company')->label('Empresa'),
                    Forms\Components\TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('phone')->label('Telefone'),
                    Forms\Components\TextInput::make('website')->label('Website')->url()->placeholder('https://exemplo.pt')->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Acesso ao portal do cliente')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->helperText('Deixa em branco para nao alterar / nao dar acesso ao portal ainda.')
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->required(fn (string $operation) => false),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Conta ativa')
                        ->default(true)
                        ->helperText('Desativa para bloquear o login no portal sem apagar o cliente.'),
                    Forms\Components\Toggle::make('is_internal')
                        ->label('Projecto interno')
                        ->default(false)
                        ->helperText('Marca como projecto interno da empresa (Horta da Maria, Ateneya, etc.) em vez de cliente externo.')
                        ->columnSpanFull(),
                ]),
            Forms\Components\Textarea::make('notes')->label('Notas internas')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('company')->label('Empresa')->searchable(),
                Tables\Columns\TextColumn::make('website')->label('Website')
                    ->url(fn ($record) => $record->website)
                    ->openUrlInNewTab()
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('phone')->label('Telefone')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('servers_count')->label('Servidores')->counts('servers'),
                Tables\Columns\IconColumn::make('is_active')->label('Ativo')->boolean(),
                Tables\Columns\IconColumn::make('is_internal')->label('Interno')
                    ->boolean()
                    ->trueIcon('heroicon-o-building-office')
                    ->falseIcon(null)
                    ->trueColor('info')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Ativo'),
                Tables\Filters\TernaryFilter::make('is_internal')
                    ->label('Tipo')
                    ->trueLabel('Só internos')
                    ->falseLabel('Só clientes externos')
                    ->placeholder('Todos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ServicesRelationManager::class,
            CredentialsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
