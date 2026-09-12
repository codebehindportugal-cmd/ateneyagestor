<?php

namespace App\Services\Cofre;

use Illuminate\Support\Facades\Session;

/**
 * Onde vive a chave do cofre enquanto ele está aberto: na sessão do servidor.
 *
 * Não vai no cookie (o cookie só leva o id da sessão), não vai para a base de
 * dados e não se escreve em log nenhum. Fechar o browser, fazer logout ou
 * passar o tempo de inactividade deixa cair a chave e o cofre volta a estar
 * trancado.
 */
class CofreSessao
{
    private const CHAVE   = 'cofre.chave';
    private const EXPIRA  = 'cofre.expira_em';

    /** Guarda a chave e começa a contar o tempo. */
    public static function destrancar(string $chave): void
    {
        Session::put(self::CHAVE, base64_encode($chave));

        self::renovar();
    }

    public static function trancar(): void
    {
        Session::forget([self::CHAVE, self::EXPIRA]);
    }

    public static function destrancado(): bool
    {
        return self::chave() !== null;
    }

    /**
     * A chave em claro, ou null se o cofre estiver trancado. Cada leitura
     * renova o tempo — quem está a trabalhar no cofre não o vê fechar-se na
     * cara.
     */
    public static function chave(): ?string
    {
        $guardada = Session::get(self::CHAVE);
        $expira   = Session::get(self::EXPIRA);

        if (! is_string($guardada) || ! is_int($expira)) {
            return null;
        }

        if (time() > $expira) {
            self::trancar();

            return null;
        }

        self::renovar();

        return base64_decode($guardada, true) ?: null;
    }

    /** Igual à anterior, mas rebenta em vez de devolver null. */
    public static function chaveObrigatoria(): string
    {
        return self::chave() ?? throw CofreException::trancado();
    }

    /** Minutos que faltam até se trancar sozinho (para mostrar no ecrã). */
    public static function minutosRestantes(): int
    {
        $expira = Session::get(self::EXPIRA);

        if (! is_int($expira)) {
            return 0;
        }

        return max(0, (int) ceil(($expira - time()) / 60));
    }

    private static function renovar(): void
    {
        $minutos = max(1, (int) config('cofre.minutos_inactividade', 15));

        Session::put(self::EXPIRA, time() + ($minutos * 60));
    }
}
