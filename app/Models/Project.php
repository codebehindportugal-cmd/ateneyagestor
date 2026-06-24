<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
