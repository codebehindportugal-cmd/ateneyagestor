<?php

namespace App\Enums;

enum SyncStatus: string
{
    case Success = 'success';
    case Partial = 'partial';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Success => 'Sucesso',
            self::Partial => 'Parcial',
            self::Failed => 'Falhou',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Success => 'success',
            self::Partial => 'warning',
            self::Failed => 'danger',
        };
    }
}
