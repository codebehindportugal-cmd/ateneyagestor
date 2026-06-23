<?php

namespace App\Enums;

enum BackupStatus: string
{
    case Success = 'success';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Success => 'Sucesso',
            self::Failed => 'Falhou',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Success => 'success',
            self::Failed => 'danger',
        };
    }
}
