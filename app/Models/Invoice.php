<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'number',
        'amount_cents',
        'currency',
        'hours',
        'status',
        'issued_at',
        'due_at',
        'paid_at',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issued_at' => 'date',
            'due_at' => 'date',
            'paid_at' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    public function markPaid(?\DateTimeInterface $when = null): void
    {
        $this->forceFill([
            'status' => InvoiceStatus::Paid,
            'paid_at' => $when ?? now(),
        ])->save();
    }
}
