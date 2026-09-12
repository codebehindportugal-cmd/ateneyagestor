<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SiteProvisionResource\Pages;
use App\Models\Client;
use App\Models\Server;
use App\Models\SiteProvision;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * "Novo site": escreve-se o domínio e o painel monta tudo no VPS escolhido.
 *
 * A lista é o histórico — dá para ver como é que cada site foi montado e
 * onde é que parou quando alguma coisa correu mal.
 */
class SiteProvisionResource extends Resource
{
    protected static ?string $model = SiteProvision::class;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?string $navigationLabel = 'Sites novos';

    protected static ?string $modelLabel = 'site novo';

    protected static ?string $pluralModelLabel = 'sites novos';

    protected static ?string $navigationGroup = 'Infraestrutura';

    protected static ?int $navigationSort = 3;

    /** O formulário do "Criar site" — usado na acção do cabeçalho da lista. */
    public static function camposDoPedido(): array
    {
        return [
            Forms\Components\TextInput::make('dominio')
                ->label('Domínio')
                ->placeholder('exemplo.pt')
                ->required()
                ->helperText('Sem https:// e sem www — o www fica a apontar para o mesmo sítio.')
                ->columnSpanFull(),

            Forms\Components\Select::make('server_id')
                ->label('Servidor')
                ->options(fn () => Server::where('is_active', true)
                    ->where(fn ($q) => $q->whereNull('panel')->orWhere('panel', '!=', 'plesk'))
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->helperText('Só aparecem as máquinas sem Plesk: nas de Plesk é o painel que cria os sites.'),

            // Sem ->relationship(): este formulário vive numa acção, não está
            // ligado a um registo, e o Filament não tem de onde tirar a relação.
            Forms\Components\Select::make('client_id')
                ->label('Cliente')
                ->options(fn () => Client::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->nullable(),

            Forms\Components\TextInput::make('email')
                ->label('Email do administrador')
                ->email()
                ->required()
                ->helperText('Vai para o WordPress e para o aviso de expiração do certificado.'),

            Forms\Components\TextInput::make('titulo')
                ->label('Título do site')
                ->placeholder('(por omissão, o domínio)'),

            Forms\Components\TextInput::make('admin')
                ->label('Utilizador administrador')
                ->default('ateneya')
                ->required()
                ->helperText('A senha é gerada e guardada no cofre.'),

            Forms\Components\Toggle::make('ssl')
                ->label('Pedir certificado Let’s Encrypt')
                ->default(true)
                ->helperText('Se o domínio ainda não apontar para o servidor, este passo é saltado com aviso.'),

            Forms\Components\Toggle::make('wordpress')
                ->label('Instalar WordPress')
                ->default(true),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('dominio')
                    ->label('Domínio')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('server.name')
                    ->label('Servidor')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => SiteProvision::estadoLabels()[$state] ?? $state)
                    ->color(fn (string $state) => SiteProvision::estadoCores()[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Quando')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('server_id')
                    ->label('Servidor')
                    ->relationship('server', 'name'),
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(SiteProvision::estadoLabels()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Ver'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('Ainda não criaste nenhum site por aqui')
            ->emptyStateDescription('Carrega em "Criar site", escreve o domínio e o resto é com o painel.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteProvisions::route('/'),
            'view'  => Pages\ViewSiteProvision::route('/{record}'),
        ];
    }
}
