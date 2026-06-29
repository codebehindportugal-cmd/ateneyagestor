<?php

namespace App\Filament\Admin\Resources;

use App\Enums\SecurityStatus;
use App\Filament\Admin\Resources\SecurityScanResource\Pages;
use App\Models\SecurityScan;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class SecurityScanResource extends Resource
{
    protected static ?string $model = SecurityScan::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Scans de SeguranÃ§a';

    protected static ?string $navigationGroup = 'Operação';

    protected static ?string $modelLabel = 'scan de seguranÃ§a';

    protected static ?string $pluralModelLabel = 'scans de seguranÃ§a';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Resumo')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('server.name')->label('Servidor'),
                    Infolists\Components\TextEntry::make('status')
                        ->label('Estado')
                        ->badge()
                        ->color(fn (SecurityStatus $state) => $state->color())
                        ->formatStateUsing(fn (SecurityStatus $state) => $state->label()),
                    Infolists\Components\TextEntry::make('findings_count')->label('Achados'),
                    Infolists\Components\TextEntry::make('started_at')->label('Iniciado')->dateTime('d/m/Y H:i:s'),
                    Infolists\Components\TextEntry::make('finished_at')->label('Terminado')->dateTime('d/m/Y H:i:s'),
                    Infolists\Components\TextEntry::make('triggered_by')->label('Origem')->badge(),
                ]),

            Infolists\Components\Section::make('Achados')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('findings')
                        ->label('')
                        ->schema([
                            Infolists\Components\TextEntry::make('label')
                                ->label('Verificação')
                                ->placeholder(fn ($record) => $record['check'] ?? '-'),
                            Infolists\Components\TextEntry::make('severity')
                                ->label('Severidade')
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'critical' => 'danger',
                                    'warning'  => 'warning',
                                    'info'     => 'gray',
                                    default    => 'gray',
                                }),
                            Infolists\Components\TextEntry::make('count')->label('Quantidade')->badge(),
                            Infolists\Components\IconEntry::make('has_findings')->label('Com achados')->boolean(),
                            Infolists\Components\TextEntry::make('items')
                                ->label('Itens encontrados')
                                ->formatStateUsing(fn ($state) => is_array($state) ? implode(PHP_EOL, $state) : ($state ?: ''))
                                ->columnSpanFull()
                                ->fontFamily('mono')
                                ->extraAttributes(['style' => 'white-space:pre-wrap;font-size:0.75rem'])
                                ->visible(fn ($state) => filled($state)),
                            Infolists\Components\TextEntry::make('raw')
                                ->label('Saída original')
                                ->columnSpanFull()
                                ->fontFamily('mono')
                                ->extraAttributes(['style' => 'white-space:pre-wrap;font-size:0.75rem'])
                                ->visible(fn ($state) => filled($state)),
                        ])
                        ->columns(4)
                        ->visible(fn (SecurityScan $record) => ! empty($record->findings)),
                ]),

            Infolists\Components\Section::make('Log de execuÃ§Ã£o')
                ->collapsed()
                ->schema([
                    Infolists\Components\TextEntry::make('log')
                        ->label('')
                        ->fontFamily('mono')
                        ->extraAttributes(['style' => 'white-space:pre-wrap;font-size:0.75rem'])
                        ->placeholder('(sem log)'),
                ]),

            Infolists\Components\Section::make('Erro')
                ->collapsed()
                ->visible(fn (SecurityScan $record) => filled($record->error))
                ->schema([
                    Infolists\Components\TextEntry::make('error')
                        ->label('')
                        ->color('danger')
                        ->fontFamily('mono')
                        ->extraAttributes(['style' => 'white-space:pre-wrap;font-size:0.75rem']),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server.name')
                    ->label('Servidor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('server.client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (SecurityStatus $state) => $state->color())
                    ->formatStateUsing(fn (SecurityStatus $state) => $state->label())
                    ->icon(fn (SecurityStatus $state) => $state->icon()),
                Tables\Columns\TextColumn::make('findings_count')
                    ->label('Achados')
                    ->placeholder('0')
                    ->sortable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Iniciado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('DuraÃ§Ã£o')
                    ->formatStateUsing(function (SecurityScan $record) {
                        $secs = $record->durationSeconds();
                        return $secs !== null ? "{$secs}s" : 'â€”';
                    })
                    ->placeholder('â€”'),
                Tables\Columns\TextColumn::make('triggered_by')
                    ->label('Origem')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'filament' => 'info',
                        'command'  => 'gray',
                        default    => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        SecurityStatus::Clean->value    => SecurityStatus::Clean->label(),
                        SecurityStatus::Warning->value  => SecurityStatus::Warning->label(),
                        SecurityStatus::Critical->value => SecurityStatus::Critical->label(),
                        SecurityStatus::Failed->value   => SecurityStatus::Failed->label(),
                        SecurityStatus::Running->value  => SecurityStatus::Running->label(),
                    ]),
                Tables\Filters\SelectFilter::make('server')
                    ->label('Servidor')
                    ->relationship('server', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Ver relatÃ³rio'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('started_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSecurityScans::route('/'),
            'view'  => Pages\ViewSecurityScan::route('/{record}'),
        ];
    }
}
