<?php

namespace App\Filament\Client\Resources;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Client\Resources\TicketResource\Pages;
use App\Filament\Client\Resources\TicketResource\RelationManagers\MessagesRelationManager;
use App\Models\Ticket;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationLabel = 'Tickets de suporte';

    protected static ?string $modelLabel = 'ticket';

    protected static ?string $pluralModelLabel = 'tickets';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('client_id', Filament::auth()->id());
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('subject')
                ->label('Assunto')
                ->required()
                ->disabledOn('edit'),
            Forms\Components\Textarea::make('message')
                ->label('Descreve o problema')
                ->required()
                ->rows(5)
                ->visibleOn('create')
                ->dehydrated(false),
            Forms\Components\Select::make('status')
                ->label('Estado')
                ->options(array_combine(
                    array_map(fn ($c) => $c->value, TicketStatus::cases()),
                    array_map(fn ($c) => $c->label(), TicketStatus::cases()),
                ))
                ->disabled()
                ->dehydrated(false)
                ->visibleOn('edit'),
            Forms\Components\Select::make('priority')
                ->label('Prioridade')
                ->options(array_combine(
                    array_map(fn ($c) => $c->value, TicketPriority::cases()),
                    array_map(fn ($c) => $c->label(), TicketPriority::cases()),
                ))
                ->default(TicketPriority::Normal->value)
                ->disabledOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject')->label('Assunto')->limit(50),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn (TicketStatus $state) => $state->color())
                    ->formatStateUsing(fn (TicketStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('priority')->label('Prioridade')->badge()
                    ->color(fn (TicketPriority $state) => $state->color())
                    ->formatStateUsing(fn (TicketPriority $state) => $state->label()),
                Tables\Columns\TextColumn::make('updated_at')->label('Atualizado')->dateTime('d/m/Y H:i'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ver / responder'),
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
