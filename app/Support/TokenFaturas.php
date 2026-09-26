<?php

namespace App\Support;

use App\Models\User;
use InvalidArgumentException;

/**
 * O token da API de faturas (/api/v1/faturas) — o que a skill do chat usa.
 *
 * So leva `faturas:write`, nunca `*`: e um token que anda a ser colado em
 * conversas, e se fugir so deve dar para lancar documentos de fornecedor, nao
 * para mexer nos agentes de backup, nos sincronizadores ou no worker do Claude.
 *
 * Emitir um novo revoga os anteriores com o mesmo nome, para nao se ir
 * acumulando tokens esquecidos com acesso a contabilidade.
 */
class TokenFaturas
{
    public const ABILITY = 'faturas:write';

    public const NOME_POR_OMISSAO = 'api-faturas';

    /** @return array{token: string, revogados: int} */
    public static function emitir(User $utilizador, ?string $nome = null): array
    {
        if (! $utilizador->isAdmin()) {
            throw new InvalidArgumentException('So um administrador pode ter token da API de faturas.');
        }

        $nome = trim((string) $nome) ?: self::NOME_POR_OMISSAO;

        $revogados = $utilizador->tokens()->where('name', $nome)->delete();

        $token = $utilizador->createToken($nome, [self::ABILITY])->plainTextToken;

        return ['token' => $token, 'revogados' => (int) $revogados];
    }

    public static function revogar(User $utilizador, ?string $nome = null): int
    {
        $nome = trim((string) $nome) ?: self::NOME_POR_OMISSAO;

        return (int) $utilizador->tokens()->where('name', $nome)->delete();
    }
}
