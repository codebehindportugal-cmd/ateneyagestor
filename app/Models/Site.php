<?php

namespace App\Models;

use App\Enums\ServerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Um site a copiar. Vive dentro de um Server (a máquina), porque o mesmo VPS
 * aloja vários domínios — e cada um pode ser de um cliente diferente e de um
 * tipo diferente (o Contabo D tem um Laravel e um WordPress lado a lado).
 *
 * Tudo o que é "como lá chegar" está no Server. Aqui só está o que se copia.
 */
class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'client_id',
        'name',
        'domain',
        'type',
        'is_active',
        'wp_root',
        'app_path',
        'storage_paths',
        'db_override',
        'plesk_backup_args',
        'api_port',
        'backup_dest',
        'poll_interval_seconds',
        'max_wait_seconds',
        'retention_keep_days',
        'retention_keep_min_copies',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type'              => ServerType::class,
            'is_active'         => 'boolean',
            'storage_paths'     => 'array',
            'db_override'       => 'array',
            'plesk_backup_args' => 'array',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function backupRuns(): HasMany
    {
        return $this->hasMany(BackupRun::class)->latest('started_at');
    }

    public function latestBackupRun(): HasOne
    {
        return $this->hasOne(BackupRun::class)->latestOfMany('started_at');
    }

    /**
     * Retenção efetiva: o que o site define, senão o que o servidor define,
     * senão nada — e aí o agente usa o valor global dele.
     */
    public function retention(): array
    {
        return array_filter([
            'keep_days' => $this->retention_keep_days
                ?: $this->server?->retention_keep_days,
            'keep_min_copies' => $this->retention_keep_min_copies
                ?: $this->server?->retention_keep_min_copies,
        ]);
    }

    /**
     * O bloco que o agente recebe dentro de "sites". Metadados apenas —
     * nunca chaves nem passwords, que vivem no secrets.yaml do agente.
     */
    public function toAgentArray(): array
    {
        $base = [
            'name' => $this->name,
            'type' => $this->type->value,
        ];

        $typeSpecific = match ($this->type) {
            ServerType::WordPress => [
                'wp_root' => $this->wp_root,
            ],
            ServerType::VpsLaravel => [
                'app_path'      => $this->app_path,
                'storage_paths' => $this->storage_paths,
                'db_override'   => $this->db_override,
            ],
            ServerType::Plesk => [
                'domain'            => $this->domain,
                'plesk_backup_args' => $this->plesk_backup_args,
            ],
            ServerType::Cpanel => [
                'domain'                => $this->domain,
                'api_port'              => $this->api_port,
                'backup_dest'           => $this->backup_dest,
                'poll_interval_seconds' => $this->poll_interval_seconds,
                'max_wait_seconds'      => $this->max_wait_seconds,
            ],
        };

        if ($retention = $this->retention()) {
            $typeSpecific['retention'] = $retention;
        }

        return array_filter(
            array_merge($base, $typeSpecific),
            fn ($value) => ! is_null($value) && $value !== []
        );
    }
}
