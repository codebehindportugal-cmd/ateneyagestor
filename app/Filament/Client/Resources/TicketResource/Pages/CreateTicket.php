<?php

namespace App\Filament\Client\Resources\TicketResource\Pages;

use App\Filament\Client\Resources\TicketResource;
use App\Models\TicketMessage;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected ?string $initialMessage = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->initialMessage = $data['message'] ?? null;
        unset($data['message']);

        $data['client_id'] = Filament::auth()->id();
        $data['status'] = 'open';

        return $data;
    }

    protected function afterCreate(): void
    {
        if (filled($this->initialMessage)) {
            TicketMessage::create([
                'ticket_id' => $this->record->id,
                'author_type' => 'client',
                'author_client_id' => Filament::auth()->id(),
                'body' => $this->initialMessage,
            ]);
        }
    }
}
