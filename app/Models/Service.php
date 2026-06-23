<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'type',
        'domain',
        'billing_cycle',
        'amount_cents',
        'renewal_date',
        'auto_renew',
        'registrar',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type'          => ServiceType::class,
            'billing_cycle' => BillingCycle::class,
            'renewal_date'  => 'date',
            'auto_renew'    => 'boolean',
            'is_active'     => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function daysUntilRenewal(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->renewal_date, false);
    }

    public function amountEuros(): float
    {
        return $this->amount_cents / 100;
    }
}
