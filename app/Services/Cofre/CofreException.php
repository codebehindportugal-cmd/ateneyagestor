<?php

namespace App\Services\Cofre;

use RuntimeException;

/** Master password errada, cofre trancado, ou dados que não abrem. */
class CofreException extends RuntimeException
{
    public static function senhaErrada(): self
    {
        return new self('A master password não está certa.');
    }

    public static function trancado(): self
    {
        return new self('O cofre está trancado.');
    }

    public static function dadosCorrompidos(): self
    {
        return new self('Não foi possível decifrar — os dados não batem certo com a chave.');
    }
}
