<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Enums\BillingCycle;
use App\Enums\ServiceType;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    protected static ?string $title = 'Serviços & Domínios';

    public function form(Form $form): Form
    {
        return $form->schema([
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
                ->placeholder('Ex: Eurodns, Namecheap'),
            Forms\Components\Select::make('billing_cycle')
                ->label('Ciclo')
                ->options(BillingCycle::class)
                ->required()
                ->default(BillingCycle::Annual),
            Forms\Components\TextInput::make('amount_cents')
                ->label('Valor (cêntimos)')
                ->numeric()
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
            Forms\Components\Toggle::make('is_active')
                ->label('Ativo')
                ->default(true),
            Forms\Components\Textarea::make('notes')
                ->label('Notas')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('renewal_date')
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Serviço')->searchable(),
                Tables\Columns\TextColumn::make('domain')->label('Domínio')->placeholder('-'),
                Tables\Columns\TextColumn::make('type')->label('Tipo')->badge(),
                Tables\Columns\TextColumn::make('billing_cycle')->label('Ciclo')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Valor')
                    ->formatStateUsing(fn (int $state) => number_format($state / 100, 2, ',', '.') . ' €'),
                Tables\Columns\TextColumn::make('renewal_date')
                    ->label('Renovação')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (Service $record) => match (true) {
                        $record->daysUntilRenewal() < 0  => 'danger',
                        $record->daysUntilRenewal() <= 30 => 'warning',
                        $record->daysUntilRenewal() <= 90 => 'info',
                        default                           => null,
                    }),
                Tables\Columns\IconColumn::make('is_active')->label('Ativo')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
