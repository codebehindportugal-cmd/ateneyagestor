<?php

namespace App\Enums;

enum BackupStatus: string
{
    case Success = 'success';
    case Failed  = 'failed';
    case Running = 'running';

    public function label(): string
    {
        return match ($this) {
            self::Success => 'Sucesso',
            self::Failed  => 'Falhou',
            self::Running => 'A correr',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Success => 'success',
            self::Failed  => 'danger',
            self::Running => 'warning',
        };
    }
}
