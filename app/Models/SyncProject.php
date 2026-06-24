<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class SyncProject extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'site_url',
        'client_id',
        'host',
        'runner_script_path',
        'runner_schedule',
        'is_active',
        'status',
        'last_run_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SyncProject $project) {
            $project->slug ??= Str::slug($project->name).'-'.Str::random(6);
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function syncRuns(): HasMany
    {
        return $this->hasMany(SyncRun::class);
    }

    public function latestSyncRun(): HasOne
    {
        return $this->hasOne(SyncRun::class)->latestOfMany('started_at');
    }

    public static function typeOptions(): array
    {
        return [
            'phc_woo' => 'PHC → WooCommerce',
            'wintouch_woo' => 'Wintouch → WooCommerce',
            'csharp' => 'C# (cliente)',
            'other' => 'Outro',
        ];
    }
}
