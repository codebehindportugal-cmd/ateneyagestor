<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'assigned_user_id',
        'subject',
        'status',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
        ];
    }

    /**
     * O `ticket_id` das mensagens tem cascadeOnDelete: a base de dados varre a
     * conversa sozinha e o Eloquent nunca sabe. Sem isto, os ficheiros que o
     * cliente anexou as mensagens ficavam com registo orfao e o ficheiro
     * esquecido no NAS. O mesmo cuidado esta no Project, pela mesma razao.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $ticket) {
            $ticket->messages->each->delete();
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->oldest();
    }
}
