<?php

namespace App\Models;

use App\Enums\SecurityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityScan extends Model
{
    protected $fillable = [
        'server_id',
        'status',
        'triggered_by',
        'findings_count',
        'findings',
        'log',
        'error',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status'      => SecurityStatus::class,
            'findings'    => 'array',
            'started_at'  => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function durationSeconds(): ?int
    {
        if (! $this->started_at || ! $this->finished_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->finished_at);
    }

    public function findingsBySeverity(string $severity): array
    {
        return array_values(array_filter(
            $this->findings ?? [],
            fn ($f) => ($f['severity'] ?? '') === $severity && ($f['has_findings'] ?? false)
        ));
    }
}
