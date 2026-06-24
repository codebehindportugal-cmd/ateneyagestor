<?php

namespace App\Models;

use App\Enums\MonitorStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteMonitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'server_id',
        'name',
        'url',
        'is_active',
        'status',
        'last_http_code',
        'last_response_ms',
        'last_error',
        'last_checked_at',
        'went_down_at',
    ];

    protected function casts(): array
    {
        return [
            'status'          => MonitorStatus::class,
            'is_active'       => 'boolean',
            'last_checked_at' => 'datetime',
            'went_down_at'    => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(SiteMonitorCheck::class);
    }

    public function recentChecks(): HasMany
    {
        return $this->hasMany(SiteMonitorCheck::class)
            ->latest('checked_at')
            ->limit(288); // last 24h at 5-min intervals
    }

    public function downtimeDuration(): ?string
    {
        if ($this->status !== MonitorStatus::Down || ! $this->went_down_at) {
            return null;
        }
        $diff = $this->went_down_at->diffForHumans(now(), true);
        return 'há ' . $diff;
    }
}
