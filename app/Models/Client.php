<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Client extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'website',
        'password',
        'notes',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Customers may access the Client (portal) panel only, and only while
     * they have a password set and their account is active. A client
     * record with no password yet (billing-only, no portal access) simply
     * can't log in -- canAccessPanel() is also re-checked by Filament on
     * every request, not just at login.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'client' && $this->is_active && filled($this->password);
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }
}
