<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Papéis. Só há dois de propósito: quem manda na casa toda e quem só vê o
     * trabalho que lhe foi dado. Tudo o que o painel deixa ou não deixa fazer
     * decide-se a partir daqui (ver app/Policies).
     */
    public const ROLE_ADMIN      = 'admin';
    public const ROLE_ESTAGIARIO = 'estagiario';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'job_title',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'role'      => self::ROLE_ESTAGIARIO,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    /**
     * Staff users may access the Admin panel (and only the Admin panel).
     * Uma conta desactivada deixa de entrar, sem ser preciso apagá-la — assim
     * o histórico do que essa pessoa fez não se perde.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->is_active;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isEstagiario(): bool
    {
        return $this->role === self::ROLE_ESTAGIARIO;
    }

    public static function roleOptions(): array
    {
        return [
            self::ROLE_ADMIN      => 'Administrador',
            self::ROLE_ESTAGIARIO => 'Estagiário',
        ];
    }

    public function roleLabel(): string
    {
        return self::roleOptions()[$this->role] ?? (string) $this->role;
    }

    public static function roleColor(?string $role): string
    {
        return $role === self::ROLE_ADMIN ? 'danger' : 'info';
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_user_id');
    }

    /** As tarefas de projecto de que esta pessoa é responsável. */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'assigned_user_id');
    }

    /** Tudo o que esta pessoa fez nas tarefas, do mais recente para trás. */
    public function taskActivities(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->latest();
    }
}
