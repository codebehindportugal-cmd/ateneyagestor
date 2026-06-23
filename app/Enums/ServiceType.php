<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ServiceType: string implements HasColor, HasIcon, HasLabel
{
    case Domain = 'domain';
    case Hosting = 'hosting';
    case Email = 'email';
    case Ssl = 'ssl';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Domain  => 'Domínio',
            self::Hosting => 'Hosting',
            self::Email   => 'Email',
            self::Ssl     => 'SSL',
            self::Other   => 'Outro',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Domain  => 'blue',
            self::Hosting => 'violet',
            self::Email   => 'amber',
            self::Ssl     => 'green',
            self::Other   => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Domain  => 'heroicon-o-globe-alt',
            self::Hosting => 'heroicon-o-server',
            self::Email   => 'heroicon-o-envelope',
            self::Ssl     => 'heroicon-o-lock-closed',
            self::Other   => 'heroicon-o-tag',
        };
    }
}
