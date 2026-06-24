<?php

namespace App\Models;

use App\Enums\ServerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'agent_id',
        'name',
        'type',
        'is_active',
        'host',
        'port',
        'user',
        'app_path',
        'storage_paths',
        'db_override',
        'domain',
        'plesk_backup_args',
        'api_port',
        'backup_dest',
        'poll_interval_seconds',
        'max_wait_seconds',
        'agent_secret_ref',
        'retention_keep_days',
        'retention_keep_min_copies',
        'notes',
        'ssh_key_path',
        'plesk_api_key',
        'wp_root',
    ];

    protected function casts(): array
    {
        return [
            'type' => ServerType::class,
            'is_active' => 'boolean',
            'storage_paths' => 'array',
            'db_override' => 'array',
            'plesk_backup_args' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function backupRuns(): HasMany
    {
        return $this->hasMany(BackupRun::class)->latest('started_at');
    }

    public function latestBackupRun(): HasOne
    {
        return $this->hasOne(BackupRun::class)->latestOfMany('started_at');
    }

    public function siteMonitors(): HasMany
    {
        return $this->hasMany(SiteMonitor::class);
    }

    /**
     * The shape agent_sync.py expects from GET /api/agent/config -- METADATA
     * ONLY, never secrets. Null/empty fields are dropped so the Pi-side
     * config.yaml this produces stays close to a hand-written one.
     */
    public function toAgentArray(): array
    {
        $base = [
            'name' => $this->name,
            'type' => $this->type->value,
            'host' => $this->host,
            'port' => $this->port,
            'agent_secret_ref' => $this->agent_secret_ref ?: $this->name,
        ];

        $typeSpecific = match ($this->type) {
            ServerType::WordPress => [
                'user'    => $this->user,
                'wp_root' => $this->wp_root,
            ],
            ServerType::VpsLaravel => [
                'user'          => $this->user,
                'app_path'      => $this->app_path,
                'storage_paths' => $this->storage_paths,
                'db_override'   => $this->db_override,
            ],
            ServerType::Plesk => [
                'user'               => $this->user,
                'domain'             => $this->domain,
                'plesk_backup_args'  => $this->plesk_backup_args,
            ],
            ServerType::Cpanel => [
                'api_port'               => $this->api_port,
                'backup_dest'            => $this->backup_dest,
                'poll_interval_seconds'  => $this->poll_interval_seconds,
                'max_wait_seconds'       => $this->max_wait_seconds,
            ],
        };

        if ($this->retention_keep_days || $this->retention_keep_min_copies) {
            $typeSpecific['retention'] = array_filter([
                'keep_days' => $this->retention_keep_days,
                'keep_min_copies' => $this->retention_keep_min_copies,
            ]);
        }

        return array_filter(array_merge($base, $typeSpecific), fn ($v) => ! is_null($v) && $v !== []);
    }
}
