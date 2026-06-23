<?php

namespace App\Filament\Admin\Resources;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Admin\Resources\TicketResource\Pages;
use App\Filament\Admin\Resources\TicketResource\RelationManagers\MessagesRelationManager;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationLabel = 'Tickets';

    protected static ?string $modelLabel = 'ticket';

    protected static ?string $pluralModelLabel = 'tickets';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('client_id')
                ->label('Cliente')
                ->relationship('client', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('subject')->label('Assunto')->required()->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->label('Estado')
                ->options(array_combine(
                    array_map(fn ($c) => $c->value, TicketStatus::cases()),
                    array_map(fn ($c) => $c->label(), TicketStatus::cases()),
                ))
                ->default(TicketStatus::Open->value)
                ->required(),
            Forms\Components\Select::make('priority')
                ->label('Prioridade')
                ->options(array_combine(
                    array_map(fn ($c) => $c->value, TicketPriority::cases()),
                    array_map(fn ($c) => $c->label(), TicketPriority::cases()),
                ))
                ->default(TicketPriority::Normal->value)
                ->required(),
            Forms\Components\Select::make('assigned_user_id')
                ->label('Atribuido a')
                ->relationship('assignedUser', 'name')
                ->searchable()
                ->preload(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject')->label('Assunto')->searchable()->limit(50),
                Tables\Columns\TextColumn::make('client.name')->label('Cliente')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn (TicketStatus $state) => $state->color())
                    ->formatStateUsing(fn (TicketStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('priority')->label('Prioridade')->badge()
                    ->color(fn (TicketPriority $state) => $state->color())
                    ->formatStateUsing(fn (TicketPriority $state) => $state->label()),
                Tables\Columns\TextColumn::make('assignedUser.name')->label('Atribuido a')->placeholder('-'),
                Tables\Columns\TextColumn::make('updated_at')->label('Atualizado')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')->options(array_combine(
                    array_map(fn ($c) => $c->value, TicketStatus::cases()),
                    array_map(fn ($c) => $c->label(), TicketStatus::cases()),
                )),
                Tables\Filters\SelectFilter::make('priority')->label('Prioridade')->options(array_combine(
                    array_map(fn ($c) => $c->value, TicketPriority::cases()),
                    array_map(fn ($c) => $c->label(), TicketPriority::cases()),
                )),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
