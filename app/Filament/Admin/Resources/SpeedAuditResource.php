<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SpeedAuditResource\Pages;
use App\Jobs\CorrerAuditoriaVelocidade;
use App\Models\Server;
use App\Models\SpeedAudit;
use App\Services\Velocidade\AuditoriaVelocidade;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Velocidade: porque é que os sites de uma máquina são lentos, e o botão para
 * corrigir cada coisa. Irmão do Endurecimento — mesma ideia, outro catálogo.
 */
class SpeedAuditResource extends Resource
{
    protected static ?string $model = SpeedAudit::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'Auditorias — velocidade';

    protected static ?string $modelLabel = 'auditoria de velocidade';

    protected static ?string $pluralModelLabel = 'auditorias de velocidade';

    protected static ?string $navigationGroup = 'Infraestrutura';

    protected static ?int $navigationSort = 7;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll(fn () => SpeedAudit::where('estado', 'pendente')->exists() ? '5s' : null)
            ->columns([
                Tables\Columns\TextColumn::make('server.name')
                    ->label('Servidor')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (SpeedAudit $r) => $r->server?->host),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => SpeedAudit::estadoLabels()[$state] ?? $state)
                    ->color(fn (string $state) => SpeedAudit::estadoCores()[$state] ?? 'gray'),
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
                        ->from((new SpeedAudit)->getTable())
                        ->groupBy('server_id'))),
                Tables\Filters\SelectFilter::make('server_id')
                    ->label('Servidor')
                    ->relationship('server', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Ver'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('Ainda não analisaste nenhum servidor')
            ->emptyStateDescription('Carrega em "Analisar servidor" — ou corre php artisan velocidade:auditar <servidor>.');
    }

    /** Cria a auditoria e manda-a para a fila. */
    public static function lancar(Server $servidor, string $lancadaPor = 'painel'): SpeedAudit
    {
        $auditoria = AuditoriaVelocidade::nova($servidor, $lancadaPor);
        CorrerAuditoriaVelocidade::dispatch($auditoria);

        return $auditoria;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpeedAudits::route('/'),
            'view'  => Pages\ViewSpeedAudit::route('/{record}'),
        ];
    }
}
