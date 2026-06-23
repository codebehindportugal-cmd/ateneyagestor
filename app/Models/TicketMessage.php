<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'author_type',
        'author_client_id',
        'author_user_id',
        'body',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function authorClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'author_client_id');
    }

    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function authorName(): string
    {
        return match ($this->author_type) {
            'client' => $this->authorClient?->name ?? 'Cliente',
            'staff' => $this->authorUser?->name ?? 'Equipa',
            default => 'Desconhecido',
        };
    }
}
