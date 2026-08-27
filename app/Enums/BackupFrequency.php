<?php

namespace App\Enums;

/**
 * Com que periodicidade um site é copiado. O agente corre todas as noites;
 * é ele que salta os mensais nos dias que não são o primeiro do mês.
 */
enum BackupFrequency: string
{
    case Daily   = 'daily';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::Daily   => 'Diário',
            self::Monthly => 'Mensal (dia 1)',
        };
    }

    /** Para o Select do Filament. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
