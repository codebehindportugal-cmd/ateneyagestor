<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MonitorStatus: string implements HasColor, HasIcon, HasLabel
{
    case Up      = 'up';
    case Down    = 'down';
    case Unknown = 'unknown';

    public function getLabel(): string
    {
        return match ($this) {
            self::Up      => 'Online',
            self::Down    => 'Offline',
            self::Unknown => 'Desconhecido',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Up      => 'success',
            self::Down    => 'danger',
            self::Unknown => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Up      => 'heroicon-o-check-circle',
            self::Down    => 'heroicon-o-x-circle',
            self::Unknown => 'heroicon-o-question-mark-circle',
        };
    }
}
