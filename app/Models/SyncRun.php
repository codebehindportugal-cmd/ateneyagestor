<?php

namespace App\Models;

use App\Enums\SyncStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'sync_project_id',
        'status',
        'products_synced',
        'orders_synced',
        'errors_count',
        'started_at',
        'finished_at',
        'log',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => SyncStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function syncProject(): BelongsTo
    {
        return $this->belongsTo(SyncProject::class);
    }

    public function durationSeconds(): ?int
    {
        if (! $this->started_at || ! $this->finished_at) {
            return null;
        }

        return (int) $this->started_at->diffInSeconds($this->finished_at);
    }
}
