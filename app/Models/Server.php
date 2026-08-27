<?php

namespace App\Models;

use App\Enums\ServerEnvironment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Uma máquina. Só guarda como lá chegar — host, porta, utilizador e a
 * referência da chave no secrets.yaml do agente. O que se copia está em
 * Site, porque o mesmo VPS aloja vários domínios.
 *
 * Nunca guarda chaves privadas nem passwords: só a referência.
 */
class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'agent_id',
        'name',
        'label',
        'panel',
        'is_active',
        'environment',
        'host',
        'port',
        'user',
        'agent_secret_ref',
        'retention_keep_days',
        'retention_keep_min_copies',
        'notes',
        'ssh_key_path',
        'plesk_api_key',
        'ping_status',
        'ping_last_checked_at',
        'ping_response_ms',
        'ping_error',
    ];

    protected function casts(): array
    {
        return [
            'is_active'            => 'boolean',
            'environment'          => ServerEnvironment::class,
            'ping_last_checked_at' => 'datetime',
        ];
    }

    /**
     * O cliente "dono" da máquina, quando existe — um VPS dedicado a um
     * cliente. Fica vazio nas máquinas partilhadas, onde quem tem cliente
     * é cada site.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function activeSites(): HasMany
    {
        return $this->hasMany(Site::class)->where('is_active', true);
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

    public function securityScans(): HasMany
    {
        return $this->hasMany(SecurityScan::class)->latest('started_at');
    }

    public function latestSecurityScan(): HasOne
    {
        return $this->hasOne(SecurityScan::class)->latestOfMany('started_at');
    }

    public function hasPlesk(): bool
    {
        return $this->panel === 'plesk';
    }

    /**
     * O que o agente recebe em GET /api/agent/config: a ligação, e dentro
     * dela os sites a copiar. Uma ligação SSH por máquina em vez de uma por
     * domínio — nos servidores com 4 sites isso são 4 ligações que passam a 1.
     */
    public function toAgentArray(): array
    {
        $sites = $this->activeSites
            ->map(fn (Site $site) => $site->toAgentArray())
            ->values()
            ->all();

        return array_filter([
            'name'             => $this->name,
            'label'            => $this->label,
            'host'             => $this->host,
            'port'             => $this->port ?: 22,
            'user'             => $this->user ?: 'root',
            'panel'            => $this->panel,
            'agent_secret_ref' => $this->agent_secret_ref ?: $this->name,
            'sites'            => $sites,
        ], fn ($value) => ! is_null($value) && $value !== []);
    }
}
