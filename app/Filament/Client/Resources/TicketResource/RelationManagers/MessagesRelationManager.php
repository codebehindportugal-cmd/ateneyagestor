<?php

namespace App\Filament\Client\Resources\TicketResource\RelationManagers;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Conversa';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('body')->label('A tua resposta')->required()->rows(4),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                Tables\Columns\TextColumn::make('authorName')->label('Quem')->state(fn ($record) => $record->authorName()),
                Tables\Columns\TextColumn::make('body')->label('Mensagem')->wrap(),
                Tables\Columns\TextColumn::make('created_at')->label('Quando')->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Responder')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['author_type'] = 'client';
                        $data['author_client_id'] = Filament::auth()->id();
                        return $data;
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'asc');
    }
}
