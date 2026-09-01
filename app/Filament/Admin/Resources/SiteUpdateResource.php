<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SiteUpdateResource\Pages;
use App\Models\SiteUpdate;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

/**
 * O historico das actualizacoes de WordPress. So de leitura: quem manda
 * actualizar e o botao na linha do site.
 */
class SiteUpdateResource extends Resource
{
    protected static ?string $model = SiteUpdate::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Operacao';

    protected static ?string $navigationLabel = 'Actualizacoes';

    protected static ?string $modelLabel = 'actualizacao';

    protected static ?string $pluralModelLabel = 'actualizacoes';

    protected static ?int $navigationSort = 40;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pendentes = SiteUpdate::whereIn('status', ['queued', 'running'])->count();

        return $pendentes > 0 ? (string) $pendentes : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('site'))
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state) => SiteUpdate::statusColors()[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state) => SiteUpdate::statusOptions()[$state] ?? $state),
                Tables\Columns\TextColumn::make('mode')
                    ->label('Modo')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state) => $state === 'dry_run' ? 'Simulacao' : 'A serio'),
                Tables\Columns\TextColumn::make('total_actualizados')
                    ->label('Actualizados')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('total_repostos')
                    ->label('Repostos')
                    ->alignCenter()
                    ->color(fn (int $state) => $state > 0 ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('agendado_para')
                    ->label('Corre a partir de')
                    ->dateTime('d/m H:i')
                    ->placeholder('—')
                    ->color(fn (SiteUpdate $record) => $record->estaAEsperaDaNoite() ? 'info' : 'gray')
                    ->visible(fn () => SiteUpdate::where('status', 'queued')->whereNotNull('agendado_para')->exists())
                    ->toggleable(),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Terminou')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->description(fn (SiteUpdate $record) => $record->isStale() ? 'preso ha mais de uma hora' : null)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('site_id')
                    ->label('Site')
                    ->relationship('site', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(SiteUpdate::statusOptions()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Ver'),
                Tables\Actions\Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('O pedido ainda nao chegou ao agente, por isso cancelar nao mexe em nada no site.')
                    ->visible(fn (SiteUpdate $record) => $record->status === 'queued')
                    ->action(function (SiteUpdate $record) {
                        $record->update([
                            'status'      => 'aborted',
                            'error'       => 'Cancelado no painel antes de arrancar.',
                            'finished_at' => now(),
                        ]);

                        Notification::make()->title('Pedido cancelado')->success()->send();
                    }),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Resultado')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('site.name')->label('Site'),
                    Infolists\Components\TextEntry::make('status')
                        ->label('Estado')
                        ->badge()
                        ->color(fn (string $state) => SiteUpdate::statusColors()[$state] ?? 'gray')
                        ->formatStateUsing(fn (string $state) => SiteUpdate::statusOptions()[$state] ?? $state),
                    Infolists\Components\TextEntry::make('finished_at')->label('Terminou')->dateTime('d/m/Y H:i'),
                    Infolists\Components\TextEntry::make('error')
                        ->label('Erro')
                        ->color('danger')
                        ->columnSpanFull()
                        ->visible(fn (SiteUpdate $record) => filled($record->error)),
                ]),

            Infolists\Components\Section::make('O que se mexeu')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('itens')
                        ->label('')
                        ->columns(4)
                        ->schema([
                            Infolists\Components\TextEntry::make('slug')->label('Item'),
                            Infolists\Components\TextEntry::make('tipo')->label('Tipo')->badge()->color('gray'),
                            Infolists\Components\TextEntry::make('versao')
                                ->label('Versao')
                                ->placeholder('—'),
                            Infolists\Components\TextEntry::make('resultado')
                                ->label('Resultado')
                                ->badge()
                                ->color(fn (?string $state) => match ($state) {
                                    'actualizado' => 'success',
                                    'reposto'     => 'warning',
                                    'falhou'      => 'danger',
                                    default       => 'gray',
                                }),
                            Infolists\Components\TextEntry::make('motivo')
                                ->label('Porque foi reposto')
                                ->color('warning')
                                ->columnSpanFull()
                                ->visible(fn (?string $state) => filled($state)),
                        ]),
                ])
                ->visible(fn (SiteUpdate $record) => filled($record->itens)),

            Infolists\Components\Section::make('Antes e depois')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Infolists\Components\KeyValueEntry::make('antes')->label('Antes'),
                    Infolists\Components\KeyValueEntry::make('depois')->label('Depois'),
                ])
                ->visible(fn (SiteUpdate $record) => filled($record->antes)),

            Infolists\Components\Section::make('Copia de reposicao')
                ->schema([
                    Infolists\Components\TextEntry::make('snapshot_path')
                        ->label('No proprio servidor')
                        ->copyable()
                        ->helperText('Ficheiros e base de dados de antes da actualizacao. Apagada automaticamente ao fim de uns dias.'),
                ])
                ->visible(fn (SiteUpdate $record) => filled($record->snapshot_path)),

            Infolists\Components\Section::make('Log')
                ->collapsed()
                ->schema([
                    Infolists\Components\TextEntry::make('log')
                        ->label('')
                        ->formatStateUsing(fn (?string $state) => new HtmlString(
                            '<pre style="white-space:pre-wrap;font-size:12px;line-height:1.5">'
                            . e((string) $state)
                            . '</pre>'
                        )),
                ])
                ->visible(fn (SiteUpdate $record) => filled($record->log)),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteUpdates::route('/'),
        ];
    }
}
