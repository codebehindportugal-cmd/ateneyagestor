<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\TokenFaturas;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Igual ao `agri:emitir-token` do Gestao Agricola, mas so com `faturas:write`.
 * O mesmo token tambem se gera no painel: Contabilidade > Acesso Contabilista >
 * "Token da API de faturas".
 */
class EmitirTokenFaturas extends Command
{
    protected $signature = 'faturas:emitir-token {email} {--nome=}';

    protected $description = 'Emite o token da API de faturas (/api/v1/faturas) para um administrador.';

    public function handle(): int
    {
        $utilizador = User::query()->where('email', $this->argument('email'))->first();

        if ($utilizador === null) {
            $this->error('Utilizador nao encontrado.');

            return self::FAILURE;
        }

        try {
            ['token' => $token, 'revogados' => $revogados] = TokenFaturas::emitir($utilizador, $this->option('nome'));
        } catch (InvalidArgumentException $excepcao) {
            $this->error($excepcao->getMessage());

            return self::FAILURE;
        }

        if ($revogados > 0) {
            $this->warn("{$revogados} token(s) anterior(es) com o mesmo nome foram revogados.");
        }

        $this->line($token);

        return self::SUCCESS;
    }
}
