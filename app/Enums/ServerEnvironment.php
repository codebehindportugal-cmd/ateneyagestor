<?php

namespace App\Enums;

enum ServerEnvironment: string
{
    case Production  = 'production';
    case Staging     = 'staging';
    case Development = 'development';

    public function label(): string
    {
        return match ($this) {
            self::Production  => 'Produção',
            self::Staging      => 'Staging',
            self::Development => 'Desenvolvimento',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Production  => 'success',
            self::Staging      => 'warning',
            self::Development => 'gray',
        };
    }
}
