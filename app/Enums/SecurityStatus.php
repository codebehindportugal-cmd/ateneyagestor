<?php

namespace App\Enums;

enum SecurityStatus: string
{
    case Running  = 'running';
    case Clean    = 'clean';
    case Warning  = 'warning';
    case Critical = 'critical';
    case Failed   = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Running  => 'A correr',
            self::Clean    => 'Limpo',
            self::Warning  => 'Aviso',
            self::Critical => 'Crítico',
            self::Failed   => 'Falhou',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Running  => 'gray',
            self::Clean    => 'success',
            self::Warning  => 'warning',
            self::Critical => 'danger',
            self::Failed   => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Running  => 'heroicon-o-arrow-path',
            self::Clean    => 'heroicon-o-shield-check',
            self::Warning  => 'heroicon-o-exclamation-triangle',
            self::Critical => 'heroicon-o-shield-exclamation',
            self::Failed   => 'heroicon-o-x-circle',
        };
    }
}
