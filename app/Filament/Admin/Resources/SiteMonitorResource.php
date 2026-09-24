<?php

namespace App\Filament\Admin\Resources;

use App\Enums\MonitorStatus;
use App\Filament\Admin\Resources\SiteMonitorResource\Pages;
use App\Models\SiteMonitor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;

class SiteMonitorResource extends Resource
{
    protected static ?string $model = SiteMonitor::class;

    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Monitorização';

    protected static ?string $modelLabel = 'monitor';

    protected static ?string $pluralModelLabel = 'monitores';

    protected static ?string $navigationGroup = 'Operação';

    protected static ?int $navigationSort = 1;

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
                Forms\Components\Select::make('server_id')
                    ->label('Servidor associado')
                    ->relationship('server', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Opcional — associa ao servidor que serve este site.'),
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->placeholder('Ex: Site Codebehind'),
                Forms\Components\TextInput::make('url')
                    ->label('URL')
                    ->required()
                    ->url()
                    ->placeholder('https://codebehindtech.com')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Ativo')
                    ->default(true),
                Forms\Components\Toggle::make('notify')
                    ->label('Avisar no telemóvel (ntfy)')
                    ->default(true)
                    ->helperText('Desligar para clientes sem manutenção: o site continua a ser verificado, mas uma queda não envia aviso nem entra no resumo das 08:00.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => self::comEstatisticas24h($query))
            ->defaultSort('client.name')
            ->poll('30s')
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label('')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Site')
                    ->searchable()
                    ->description(fn (SiteMonitor $r) => $r->url),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('server.name')
                    ->label('Servidor')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_http_code')
                    ->label('HTTP')
                    ->placeholder('—')
                    ->badge()
                    ->color(fn (?int $state) => match (true) {
                        $state === null      => 'gray',
                        $state < 300         => 'success',
                        $state < 400         => 'info',
                        default              => 'danger',
                    }),
                Tables\Columns\TextColumn::make('last_response_ms')
                    ->label('Tempo')
                    ->tooltip('Tempo total da última verificação (inclui redirects). Por baixo: TTFB, o tempo até o servidor começar a responder.')
                    ->sortable()
                    ->placeholder('—')
                    ->getStateUsing(fn (SiteMonitor $r) => $r->last_response_ms ?? ($r->last_error ? 'sem resposta' : null))
                    ->formatStateUsing(fn ($state) => is_numeric($state) ? self::ms((int) $state) : $state)
                    ->description(fn (SiteMonitor $r) => $r->last_ttfb_ms !== null ? 'TTFB ' . self::ms($r->last_ttfb_ms) : null)
                    ->color(fn ($state) => is_numeric($state) ? self::corTempo((int) $state) : 'danger'),
                Tables\Columns\TextColumn::make('media_24h')
                    ->label('Média 24h')
                    ->tooltip('Média das verificações com resposta nas últimas 24 h. Uma medição isolada varia muito (cache fria, backups); é por esta que se vê se um site é lento.')
                    ->sortable()
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => $state !== null ? self::ms((int) round($state)) : '—')
                    ->description(fn (SiteMonitor $r) => $r->ttfb_24h !== null ? 'TTFB ' . self::ms((int) round($r->ttfb_24h)) : null)
                    ->color(fn ($state) => $state !== null ? self::corTempo((int) round($state)) : 'gray'),
                Tables\Columns\TextColumn::make('uptime_24h')
                    ->label('Uptime 24h')
                    ->placeholder('—')
                    ->getStateUsing(fn (SiteMonitor $r) => $r->checks_24h ? round(100 * $r->up_24h / $r->checks_24h, 1) : null)
                    ->formatStateUsing(fn ($state) => $state !== null ? "{$state}%" : '—')
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state >= 99.5  => 'success',
                        $state >= 95    => 'warning',
                        default         => 'danger',
                    }),
                Tables\Columns\TextColumn::make('last_final_url')
                    ->label('Redirecciona para')
                    ->placeholder('—')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_error')
                    ->label('Erro')
                    ->placeholder('—')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_checked_at')
                    ->label('Última verificação')
                    ->since()
                    ->placeholder('Nunca'),
                Tables\Columns\ToggleColumn::make('notify')
                    ->label('Avisos'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(MonitorStatus::class),
                Tables\Filters\SelectFilter::make('server_id')
                    ->label('Servidor')
                    ->relationship('server', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')->label('Ativo'),
                Tables\Filters\TernaryFilter::make('notify')
                    ->label('Avisos ntfy')
                    ->trueLabel('Só com avisos')
                    ->falseLabel('Só sem avisos'),
            ])
            ->actions([
                Tables\Actions\Action::make('check_now')
                    ->label('Verificar agora')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function (SiteMonitor $record) {
                        Artisan::call('monitor:sites', ['--id' => $record->id, '--sem-repetir' => true]);
                        $record->refresh();
                        Notification::make()
                            ->title('Verificação concluída: ' . $record->status->getLabel())
                            ->color($record->status->getColor())
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('check_all')
                    ->label('Verificar todos')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        // Com 30 sites e alguns timeouts isto passa do limite
                        // de tempo de um pedido web: vai para a fila.
                        Artisan::queue('monitor:sites');
                        Notification::make()
                            ->title('Verificação a correr em segundo plano')
                            ->body('A lista actualiza sozinha dentro de 1–2 minutos.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('silenciar')
                        ->label('Desligar avisos')
                        ->icon('heroicon-o-bell-slash')
                        ->color('gray')
                        ->action(fn ($records) => SiteMonitor::whereKey($records->modelKeys())->update(['notify' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('avisar')
                        ->label('Ligar avisos')
                        ->icon('heroicon-o-bell')
                        ->action(fn ($records) => SiteMonitor::whereKey($records->modelKeys())->update(['notify' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Médias e uptime das últimas 24 h, calculados na mesma query da lista. */
    public static function comEstatisticas24h(Builder $query): Builder
    {
        $desde = now()->subDay();

        return $query
            ->withAvg(['checks as media_24h' => fn ($q) => $q->where('checked_at', '>=', $desde)->where('status', 'up')], 'response_ms')
            ->withAvg(['checks as ttfb_24h' => fn ($q) => $q->where('checked_at', '>=', $desde)->where('status', 'up')], 'ttfb_ms')
            ->withCount([
                'checks as checks_24h' => fn ($q) => $q->where('checked_at', '>=', $desde),
                'checks as up_24h'     => fn ($q) => $q->where('checked_at', '>=', $desde)->where('status', 'up'),
            ]);
    }

    public static function ms(int $ms): string
    {
        return $ms >= 1000 ? number_format($ms / 1000, 1, ',', '') . ' s' : "{$ms} ms";
    }

    /** < 0,8 s rápido · < 2 s aceitável · acima disso lento. */
    public static function corTempo(int $ms): string
    {
        return match (true) {
            $ms < 800  => 'success',
            $ms < 2000 => 'warning',
            default    => 'danger',
        };
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Admin\Resources\SiteMonitorResource\RelationManagers\ChecksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSiteMonitors::route('/'),
            'create' => Pages\CreateSiteMonitor::route('/create'),
            'edit'   => Pages\EditSiteMonitor::route('/{record}/edit'),
        ];
    }
}
