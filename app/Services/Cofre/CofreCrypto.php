<?php

namespace App\Services\Cofre;

/**
 * A criptografia do cofre pessoal, num sítio só.
 *
 * Como funciona, em três linhas:
 *
 *  1. Cada cofre tem uma chave aleatória de 32 bytes (a "chave do cofre").
 *     É ela que cifra as senhas — nunca a master password directamente.
 *  2. A master password passa pelo Argon2id e dá uma segunda chave, que serve
 *     só para embrulhar (cifrar) a chave do cofre. É o embrulho que fica na
 *     base de dados.
 *  3. Ao desbloquear, desembrulha-se a chave do cofre e guarda-se na sessão do
 *     servidor enquanto a pessoa lá anda. Fechada a sessão, fica outra vez só
 *     o embrulho.
 *
 * Consequência prática: mudar a master password é reembrulhar a mesma chave —
 * as entradas não se tocam. E quem apanhe a base de dados, com .env e tudo,
 * não tem por onde pegar sem a master password.
 */
class CofreCrypto
{
    /** Salt novo para o Argon2id. */
    public static function salt(): string
    {
        return random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
    }

    /** Chave do cofre nova — é esta que cifra as senhas. */
    public static function chaveNova(): string
    {
        return random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }

    /** Master password (ou código de recuperação) -> chave de embrulho. */
    public static function derivar(string $segredo, string $salt, int $opslimit, int $memlimit): string
    {
        return sodium_crypto_pwhash(
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
            $segredo,
            $salt,
            $opslimit,
            $memlimit,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13,
        );
    }

    /** Cifra e devolve base64(nonce + cifra). */
    public static function cifrar(string $claro, string $chave): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return base64_encode($nonce.sodium_crypto_secretbox($claro, $nonce, $chave));
    }

    /** O caminho inverso. Rebenta se a chave estiver errada ou os dados mexidos. */
    public static function decifrar(string $pacote, string $chave): string
    {
        $bruto = base64_decode($pacote, true);

        if ($bruto === false || strlen($bruto) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw CofreException::dadosCorrompidos();
        }

        $nonce = substr($bruto, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cifra = substr($bruto, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $claro = sodium_crypto_secretbox_open($cifra, $nonce, $chave);

        if ($claro === false) {
            throw CofreException::dadosCorrompidos();
        }

        return $claro;
    }

    /**
     * Senha aleatória. Sem caracteres que se confundem a ler em voz alta ou a
     * copiar à mão (l, I, 1, O, 0) — o gerador é para servir, não para irritar.
     */
    public static function gerarSenha(?int $tamanho = null, bool $simbolos = true): string
    {
        $tamanho = max(8, $tamanho ?? (int) config('cofre.tamanho_senha_gerada', 20));

        $alfabeto = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        if ($simbolos) {
            $alfabeto .= '!@#$%*-_=+?';
        }

        $ultimo = strlen($alfabeto) - 1;
        $senha  = '';

        for ($i = 0; $i < $tamanho; $i++) {
            $senha .= $alfabeto[random_int(0, $ultimo)];
        }

        return $senha;
    }

    /**
     * Código de recuperação: 8 grupos de 5 caracteres, alfabeto sem letras
     * ambíguas, para se escrever num papel e guardar fora do computador.
     */
    public static function gerarCodigoRecuperacao(): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $ultimo   = strlen($alfabeto) - 1;
        $grupos   = [];

        for ($g = 0; $g < 8; $g++) {
            $grupo = '';

            for ($i = 0; $i < 5; $i++) {
                $grupo .= $alfabeto[random_int(0, $ultimo)];
            }

            $grupos[] = $grupo;
        }

        return implode('-', $grupos);
    }

    /** Tira espaços e maiúsculas/minúsculas do código antes de o comparar. */
    public static function normalizarCodigo(string $codigo): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $codigo) ?? '');
    }
}
