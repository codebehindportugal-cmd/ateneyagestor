<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BillingCycle: string implements HasLabel
{
    case Monthly  = 'monthly';
    case Annual   = 'annual';
    case Biennial = 'biennial';

    public function getLabel(): string
    {
        return match ($this) {
            self::Monthly  => 'Mensal',
            self::Annual   => 'Anual',
            self::Biennial => 'Bienal',
        };
    }
}
