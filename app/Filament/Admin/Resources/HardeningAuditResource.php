<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\HardeningAuditResource\Pages;
use App\Models\HardeningAudit;
use App\Models\Server;
use App\Jobs\CorrerAuditoriaEndurecimento;
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

    protected static ?string $navigationLabel = 'Auditorias — segurança';

    protected static ?string $modelLabel = 'auditoria';

    protected static ?string $pluralModelLabel = 'auditorias de endurecimento';

    protected static ?string $navigationGroup = 'Infraestrutura';

    protected static ?int $navigationSort = 6;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            // As auditorias correm na fila: enquanto houver alguma a correr, a
            // listagem vai-se actualizando sozinha.
            ->poll(fn () => HardeningAudit::where('estado', 'pendente')->exists() ? '5s' : null)
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
                // Uma linha por servidor, a mais recente: com "Auditar todos" a
                // listagem enchia-se de repetidas e a de há 6 dias aparecia ao
                // lado da de hoje. O histórico fica a um clique — desliga-se o filtro.
                Tables\Filters\Filter::make('mais_recente')
                    ->label('Só a mais recente de cada servidor')
                    ->toggle()
                    ->default()
                    ->query(fn ($query) => $query->whereIn('id', fn ($sub) => $sub
                        ->selectRaw('max(id)')
                        ->from((new HardeningAudit)->getTable())
                        ->groupBy('server_id'))),
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
    /**
     * Cria a auditoria "a correr" e manda-a para a fila. Antes corria dentro do
     * pedido web e, com o apt-get update de algumas máquinas, dava 504.
     */
    public static function auditar(Server $servidor, string $lancadaPor = 'painel'): HardeningAudit
    {
        $auditoria = AuditoriaEndurecimento::nova($servidor, $lancadaPor);
        CorrerAuditoriaEndurecimento::dispatch($servidor, $lancadaPor, $auditoria);

        Notification::make()
            ->title("{$servidor->name} na fila")
            ->body('A página actualiza sozinha quando a auditoria acabar.')
            ->info()
            ->send();

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
