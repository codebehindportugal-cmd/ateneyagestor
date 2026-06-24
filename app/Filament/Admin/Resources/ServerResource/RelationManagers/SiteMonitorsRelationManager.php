<?php

namespace App\Filament\Admin\Resources\ServerResource\RelationManagers;

use App\Enums\MonitorStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;
use App\Models\SiteMonitor;

class SiteMonitorsRelationManager extends RelationManager
{
    protected static string $relationship = 'siteMonitors';

    protected static ?string $title = 'Sites monitorizados';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nome')
                ->required()
                ->placeholder('Ex: Site principal'),
            Forms\Components\TextInput::make('url')
                ->label('URL')
                ->required()
                ->url()
                ->placeholder('https://exemplo.pt')
                ->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')
                ->label('Ativo')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label('')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Site')
                    ->description(fn (SiteMonitor $r) => $r->url),
                Tables\Columns\TextColumn::make('last_http_code')
                    ->label('HTTP')
                    ->placeholder('—')
                    ->badge()
                    ->color(fn (?int $state) => match (true) {
                        $state === null => 'gray',
                        $state < 300   => 'success',
                        $state < 400   => 'info',
                        default        => 'danger',
                    }),
                Tables\Columns\TextColumn::make('last_response_ms')
                    ->label('Tempo')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?int $state) => $state ? "{$state} ms" : '—'),
                Tables\Columns\TextColumn::make('last_checked_at')
                    ->label('Última verificação')
                    ->since()
                    ->placeholder('Nunca'),
            ])
            ->actions([
                Tables\Actions\Action::make('check_now')
                    ->label('Verificar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function (SiteMonitor $record) {
                        Artisan::call('monitor:sites', ['--id' => $record->id]);
                        $record->refresh();
                        Notification::make()
                            ->title('Verificação: ' . $record->status->getLabel())
                            ->color($record->status->getColor())
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
