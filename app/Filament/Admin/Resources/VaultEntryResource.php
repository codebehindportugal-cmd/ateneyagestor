<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\VaultEntryResource\Pages;
use App\Models\VaultEntry;
use App\Services\Cofre\CofreCrypto;
use App\Services\Cofre\CofreSessao;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * As senhas privadas de quem está com sessão iniciada.
 *
 * Duas regras que valem para tudo o que está aqui:
 *
 *  - a consulta é sempre limitada ao próprio (ver getEloquentQuery);
 *  - a senha nunca entra na tabela, só no modal de "Ver senha". Uma listagem
 *    com trinta senhas decifradas seria trinta senhas em claro no HTML da
 *    página, à espera de um screenshot ou de um cache.
 */
class VaultEntryResource extends Resource
{
    protected static ?string $model = VaultEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Senhas privadas';

    protected static ?string $modelLabel = 'senha privada';

    protected static ?string $pluralModelLabel = 'senhas privadas';

    protected static ?string $navigationGroup = 'Infraestrutura';

    protected static ?int $navigationSort = 7;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('titulo')
                    ->label('Título')
                    ->required()
                    ->placeholder('Ex: Continente Online')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('pasta')
                    ->label('Pasta')
                    ->placeholder('Pessoal, Bancos, Compras…')
                    ->datalist(fn () => VaultEntry::minhas()
                        ->whereNotNull('pasta')
                        ->distinct()
                        ->orderBy('pasta')
                        ->pluck('pasta')
                        ->all()),

                Forms\Components\TextInput::make('utilizador')
                    ->label('Utilizador')
                    ->placeholder('email ou nome de utilizador'),

                Forms\Components\TextInput::make('url')
                    ->label('Endereço')
                    ->url()
                    ->placeholder('https://…')
                    ->columnSpanFull(),

                // O campo `senha` não existe na tabela: é traduzido para
                // `segredo` (cifrado) nas páginas de criar e editar.
                Forms\Components\TextInput::make('senha')
                    ->label('Senha')
                    ->password()
                    ->revealable()
                    ->required()
                    ->columnSpanFull()
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('gerar')
                            ->label('Gerar')
                            ->icon('heroicon-m-sparkles')
                            ->action(fn (Set $set) => $set('senha', CofreCrypto::gerarSenha()))
                    ),

                Forms\Components\Textarea::make('notas_claras')
                    ->label('Notas')
                    ->helperText('Também ficam cifradas.')
                    ->rows(4)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('titulo')
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('pasta')
                    ->label('Pasta')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('utilizador')
                    ->label('Utilizador')
                    ->placeholder('—')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Utilizador copiado!'),

                Tables\Columns\TextColumn::make('url')
                    ->label('Endereço')
                    ->placeholder('—')
                    ->url(fn (?string $state) => $state)
                    ->openUrlInNewTab()
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('usado_em')
                    ->label('Usada')
                    ->since()
                    ->placeholder('nunca')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pasta')
                    ->label('Pasta')
                    ->options(fn () => VaultEntry::minhas()
                        ->whereNotNull('pasta')
                        ->distinct()
                        ->orderBy('pasta')
                        ->pluck('pasta', 'pasta')
                        ->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver senha')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->modalHeading(fn (VaultEntry $record) => $record->titulo)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->modalWidth('md')
                    ->visible(fn () => CofreSessao::destrancado())
                    ->modalContent(function (VaultEntry $record) {
                        $record->marcarUsada();

                        return view('filament.cofre.ver-senha', [
                            'entrada' => $record,
                            'senha'   => $record->senha(),
                            'notas'   => $record->notasClaras(),
                        ]);
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Ainda não há senhas aqui')
            ->emptyStateDescription('Cria uma à mão, ou importa o teu ficheiro do KeePass.')
            ->searchable();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVaultEntries::route('/'),
            'create' => Pages\CreateVaultEntry::route('/create'),
            'edit'   => Pages\EditVaultEntry::route('/{record}/edit'),
        ];
    }
}
