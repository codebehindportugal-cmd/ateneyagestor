<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/**
 * Represents a Pi (or any machine) running agent_sync.py. Authenticates to
 * /api/agent/* via a Sanctum personal access token -- create one from the
 * admin panel (Agents > a row > "Gerar novo token"). The token is shown
 * once; paste it into agent_config.yaml on the Pi as `api.token`.
 *
 * This is NOT a login-capable account (no canAccessPanel/password) -- it
 * only ever authenticates as a bearer token against the agent API routes.
 */
class Agent extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'last_seen_at',
        'backup_root',
        'retention_keep_days',
        'retention_keep_min_copies',
        'log_level',
        'notify_webhook_enabled',
        'notify_webhook_url',
        'notify_sendmail_enabled',
        'notify_sendmail_to',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'notify_webhook_enabled' => 'boolean',
            'notify_sendmail_enabled' => 'boolean',
        ];
    }

    /**
     * The "global" block of GET /api/agent/config -- matches the shape
     * pi_backup/config.py expects under the `global:` key.
     */
    public function toAgentGlobalArray(): array
    {
        return [
            'backup_root' => $this->backup_root,
            'retention' => [
                'keep_days' => $this->retention_keep_days,
                'keep_min_copies' => $this->retention_keep_min_copies,
            ],
            'logging' => [
                'level' => $this->log_level,
            ],
            'notify' => [
                'on_failure_only' => true,
                'webhook' => [
                    'enabled' => (bool) $this->notify_webhook_enabled,
                    'url' => $this->notify_webhook_url,
                ],
                'sendmail' => [
                    'enabled' => (bool) $this->notify_sendmail_enabled,
                    'to' => $this->notify_sendmail_to,
                ],
            ],
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Agent $agent) {
            $agent->slug ??= Str::slug($agent->name).'-'.Str::random(6);
        });
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function backupRuns(): HasMany
    {
        return $this->hasMany(BackupRun::class);
    }

    public function markOnline(): void
    {
        $this->forceFill(['status' => 'online', 'last_seen_at' => now()])->save();
    }
}
