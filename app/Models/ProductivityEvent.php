<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductivityEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'device_uid',
        'hostname',
        'event_type',
        'app_name',
        'process_name',
        'domain',
        'activity_state',
        'started_at',
        'ended_at',
        'duration_seconds',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
