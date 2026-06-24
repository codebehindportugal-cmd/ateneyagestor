<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteMonitorCheck extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'site_monitor_id',
        'status',
        'http_code',
        'response_ms',
        'error',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(SiteMonitor::class, 'site_monitor_id');
    }
}
