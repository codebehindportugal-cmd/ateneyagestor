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
use Illuminate\Support\Facades\Artisan;

class SiteMonitorResource extends Resource
{
    protected static ?string $model = SiteMonitor::class;

    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Monitorização';

    protected static ?string $modelLabel = 'monitor';

    protected static ?string $pluralModelLabel = 'monitores';

    protected static ?string $navigationGroup = 'Operacao';

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
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                    ->placeholder('—')
                    ->formatStateUsing(fn (?int $state) => $state ? "{$state} ms" : '—')
                    ->color(fn (?int $state) => match (true) {
                        $state === null   => 'gray',
                        $state < 500      => 'success',
                        $state < 2000     => 'warning',
                        default           => 'danger',
                    }),
                Tables\Columns\TextColumn::make('last_error')
                    ->label('Erro')
                    ->placeholder('—')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_checked_at')
                    ->label('Última verificação')
                    ->since()
                    ->placeholder('Nunca'),
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
                Tables\Filters\TernaryFilter::make('is_active')->label('Ativo'),
            ])
            ->actions([
                Tables\Actions\Action::make('check_now')
                    ->label('Verificar agora')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function (SiteMonitor $record) {
                        Artisan::call('monitor:sites', ['--id' => $record->id]);
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
                        Artisan::call('monitor:sites');
                        Notification::make()->title('Verificação concluída')->success()->send();
                    }),
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
