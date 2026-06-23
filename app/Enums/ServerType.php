<?php

namespace App\Enums;

enum ServerType: string
{
    case VpsLaravel = 'vps_laravel';
    case Plesk      = 'plesk';
    case Cpanel     = 'cpanel';
    case WordPress  = 'wordpress';

    public function label(): string
    {
        return match ($this) {
            self::VpsLaravel => 'VPS (Laravel)',
            self::Plesk      => 'Plesk',
            self::Cpanel     => 'cPanel',
            self::WordPress  => 'WordPress',
        };
    }
}
