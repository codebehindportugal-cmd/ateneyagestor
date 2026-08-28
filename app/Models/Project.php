<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Project extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_internal',
        'client_id',
        'server_id',
        'type',
        'status',
        'url',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function tasksTotal(): int
    {
        return $this->tasks_count ?? $this->tasks()->count();
    }

    public function tasksDone(): int
    {
        return $this->tasks_done_count ?? $this->tasks()->where('status', 'done')->count();
    }

    public function tasksOpen(): int
    {
        return max(0, $this->tasksTotal() - $this->tasksDone());
    }

    /** Percentagem de tarefas concluídas (0-100). */
    public function progressPercent(): int
    {
        $total = $this->tasksTotal();

        return $total > 0 ? (int) round($this->tasksDone() / $total * 100) : 0;
    }

    public static function typeOptions(): array
    {
        return [
            'laravel'     => 'Laravel',
            'wordpress'   => 'WordPress',
            'woocommerce' => 'WooCommerce',
            'sync'        => 'Sincronizador',
            'other'       => 'Outro',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'active'      => 'Activo',
            'development' => 'Em desenvolvimento',
            'suspended'   => 'Suspenso',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }
}
