<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberto',
            self::Pending => 'Pendente',
            self::Closed => 'Fechado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Pending => 'info',
            self::Closed => 'success',
        };
    }
}
