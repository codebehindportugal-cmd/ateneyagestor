<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\HardeningAuditResource\Pages;
use App\Models\HardeningAudit;
use App\Models\Server;
use App\Services\Seguranca\AuditoriaEndurecimento;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Endurecimento dos servidores: o que está por apertar em cada máquina.
 *
 * Ao lado do "Security Scans" que já existe, que é outra coisa — esse procura
 * sinais de invasão, este olha para a configuração.
 */
class HardeningAuditResource extends Resource
{
    protected static ?string $model = HardeningAudit::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Endurecimento';

    protected static ?string $modelLabel = 'auditoria';

    protected static ?string $pluralModelLabel = 'auditorias de endurecimento';

    protected static ?string $navigationGroup = 'Infraestrutura';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('server.name')
                    ->label('Servidor')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (HardeningAudit $r) => $r->server?->host),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => HardeningAudit::estadoLabels()[$state] ?? $state)
                    ->color(fn (string $state) => HardeningAudit::estadoCores()[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('falhas')
                    ->label('Por corrigir')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('avisos')
                    ->label('Avisos')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('total')
                    ->label('Verificações')
                    ->toggleable(),

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
                    ->options(HardeningAudit::estadoLabels()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Ver'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('Ainda não auditaste nenhum servidor')
            ->emptyStateDescription('Carrega em "Auditar servidor" — ou corre php artisan seguranca:auditar --todos.');
    }

    /** Usada pela listagem e pela ficha do servidor. */
    public static function auditar(Server $servidor, string $lancadaPor = 'painel'): HardeningAudit
    {
        // As verificações passam por um apt-get update; num VPS lento isso
        // ultrapassa à vontade o tempo normal de um pedido web.
        @set_time_limit(0);

        $auditoria = app(AuditoriaEndurecimento::class)->correr($servidor, $lancadaPor);

        if ($auditoria->estado === 'erro') {
            Notification::make()
                ->title("Não deu para auditar {$servidor->name}")
                ->body($auditoria->erro)
                ->danger()
                ->send();
        } else {
            Notification::make()
                ->title("{$servidor->name}: {$auditoria->falhas} por corrigir, {$auditoria->avisos} avisos")
                ->color($auditoria->falhas > 0 ? 'danger' : 'success')
                ->send();
        }

        return $auditoria;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHardeningAudits::route('/'),
            'view'  => Pages\ViewHardeningAudit::route('/{record}'),
        ];
    }
}
