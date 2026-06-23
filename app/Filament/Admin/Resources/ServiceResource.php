<?php

namespace App\Filament\Admin\Resources;

use App\Enums\BillingCycle;
use App\Enums\InvoiceStatus;
use App\Enums\ServiceType;
use App\Filament\Admin\Resources\ServiceResource\Pages;
use App\Models\Invoice;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Serviços / Domínios';

    protected static ?string $modelLabel = 'serviço';

    protected static ?string $pluralModelLabel = 'serviços';

    protected static ?string $navigationGroup = 'Clientes';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificação')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('client_id')
                        ->label('Cliente')
                        ->relationship('client', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->options(ServiceType::class)
                        ->required()
                        ->default(ServiceType::Domain)
                        ->live(),
                    Forms\Components\TextInput::make('name')
                        ->label('Nome / descrição')
                        ->required()
                        ->placeholder('Ex: Domínio codebehind.pt')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('domain')
                        ->label('Domínio')
                        ->placeholder('codebehind.pt')
                        ->visible(fn (Forms\Get $get) => in_array($get('type'), [
                            ServiceType::Domain->value, ServiceType::Ssl->value, ServiceType::Hosting->value,
                        ])),
                    Forms\Components\TextInput::make('registrar')
                        ->label('Registar / fornecedor')
                        ->placeholder('Ex: Eurodns, Namecheap, GoDaddy'),
                ]),

            Forms\Components\Section::make('Faturação')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('billing_cycle')
                        ->label('Ciclo de faturação')
                        ->options(BillingCycle::class)
                        ->required()
                        ->default(BillingCycle::Annual),
                    Forms\Components\TextInput::make('amount_cents')
                        ->label('Valor (€)')
                        ->numeric()
                        ->step(1)
                        ->suffix('cêntimos')
                        ->helperText('Ex: 1200 = 12,00 €')
                        ->required()
                        ->default(0),
                    Forms\Components\DatePicker::make('renewal_date')
                        ->label('Data de renovação')
                        ->required()
                        ->displayFormat('d/m/Y'),
                    Forms\Components\Toggle::make('auto_renew')
                        ->label('Renovação automática')
                        ->default(true),
                ]),

            Forms\Components\Section::make('Estado')
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Ativo')
                        ->default(true),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notas')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('renewal_date')
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Serviço')
                    ->searchable(),
                Tables\Columns\TextColumn::make('domain')
                    ->label('Domínio')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('billing_cycle')
                    ->label('Ciclo')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Valor')
                    ->formatStateUsing(fn (int $state) => number_format($state / 100, 2, ',', '.') . ' €')
                    ->sortable(),
                Tables\Columns\TextColumn::make('renewal_date')
                    ->label('Renovação')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn (Service $record) => match (true) {
                        $record->daysUntilRenewal() < 0   => 'Vencido há ' . abs($record->daysUntilRenewal()) . ' dias',
                        $record->daysUntilRenewal() === 0  => 'Hoje',
                        default                            => 'em ' . $record->daysUntilRenewal() . ' dias',
                    })
                    ->color(fn (Service $record) => match (true) {
                        $record->daysUntilRenewal() < 0   => 'danger',
                        $record->daysUntilRenewal() <= 30  => 'warning',
                        $record->daysUntilRenewal() <= 90  => 'info',
                        default                            => null,
                    }),
                Tables\Columns\IconColumn::make('auto_renew')
                    ->label('Auto')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(ServiceType::class),
                Tables\Filters\SelectFilter::make('billing_cycle')
                    ->label('Ciclo')
                    ->options(BillingCycle::class),
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Renovam em 30 dias')
                    ->query(fn (Builder $query) => $query->where('renewal_date', '<=', now()->addDays(30))->where('renewal_date', '>=', now())),
                Tables\Filters\Filter::make('overdue')
                    ->label('Vencidos')
                    ->query(fn (Builder $query) => $query->where('renewal_date', '<', now())),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Ativo'),
            ])
            ->actions([
                Tables\Actions\Action::make('faturar')
                    ->label('Faturar')
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('description')
                            ->label('Descrição na pré-fatura')
                            ->required()
                            ->default(fn (Service $record) => 'Renovação ' . $record->name . ' (' . $record->billing_cycle->getLabel() . ')'),
                        Forms\Components\TextInput::make('amount_cents')
                            ->label('Valor (cêntimos)')
                            ->numeric()
                            ->required()
                            ->default(fn (Service $record) => $record->amount_cents),
                    ])
                    ->action(function (Service $record, array $data) {
                        $invoice = Invoice::create([
                            'client_id'    => $record->client_id,
                            'number'       => 'PFAT-' . now()->format('Y') . '-' . Str::upper(Str::random(4)),
                            'amount_cents' => $data['amount_cents'],
                            'currency'     => 'EUR',
                            'status'       => InvoiceStatus::Draft,
                            'description'  => $data['description'],
                            'issued_at'    => now(),
                        ]);
                        Notification::make()
                            ->title('Pré-fatura criada: ' . $invoice->number)
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit'   => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
