<?php

namespace App\Services\Moloni;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * De onde vêm as credenciais do Moloni (01/10/2026).
 *
 * Antes eram só do .env, o que obrigava a ir ao servidor para as pôr ou
 * mudar. Agora há a página Contabilidade > Moloni, que as guarda na tabela
 * `settings`: o client_secret e a password cifrados com a APP_KEY (quem tem
 * só a base de dados não as lê), o resto em claro.
 *
 * Regra: o que estiver na página ganha; o que lá estiver vazio cai para o
 * .env. Assim um servidor que já tinha tudo no .env continua igual.
 */
class MoloniConfig
{
    public const SEGREDOS = ['client_secret', 'password'];

    public const CAMPOS = ['client_id', 'client_secret', 'username', 'password', 'company_id'];

    /** A configuração completa, no formato de config('moloni'). */
    public static function todas(): array
    {
        $config = (array) config('moloni');

        try {
            $ligado = Setting::get('moloni.enabled');
            if ($ligado !== null) {
                $config['enabled'] = filter_var($ligado, FILTER_VALIDATE_BOOL);
            }

            foreach (self::CAMPOS as $campo) {
                $valor = self::lerGuardado($campo);
                if (filled($valor)) {
                    $config[$campo] = $campo === 'company_id' ? (int) $valor : $valor;
                }
            }
        } catch (\Throwable $e) {
            // Sem tabela (primeiro deploy) ou APP_KEY trocada: fica o .env.
            report($e);
        }

        return $config;
    }

    public static function ligado(): bool
    {
        return (bool) (self::todas()['enabled'] ?? false);
    }

    /** O valor guardado na página (já decifrado), ou null. */
    public static function lerGuardado(string $campo): ?string
    {
        $valor = Setting::get("moloni.{$campo}");

        if (blank($valor)) {
            return null;
        }

        return in_array($campo, self::SEGREDOS, true) ? Crypt::decryptString($valor) : (string) $valor;
    }

    /**
     * Grava o que veio da página. Um segredo deixado em branco não apaga o
     * que lá está — o formulário nunca o mostra, e gravar o vazio por cima
     * obrigava a escrevê-lo outra vez a cada mudança noutro campo.
     */
    public static function guardar(bool $ligado, array $dados): void
    {
        Setting::set('moloni.enabled', $ligado ? '1' : '0');

        foreach (self::CAMPOS as $campo) {
            $valor = trim((string) ($dados[$campo] ?? ''));

            if (in_array($campo, self::SEGREDOS, true)) {
                if ($valor !== '') {
                    Setting::set("moloni.{$campo}", Crypt::encryptString($valor));
                }

                continue;
            }

            Setting::set("moloni.{$campo}", $valor === '' ? null : $valor);
        }

        // Os tokens em cache eram da conta anterior: obriga a novo login.
        Cache::forget('moloni.tokens');
    }

    /** Apaga um segredo guardado na página (volta a valer o do .env, se houver). */
    public static function esquecer(string $campo): void
    {
        Setting::set("moloni.{$campo}", null);
        Cache::forget('moloni.tokens');
    }

    /** De onde vem cada campo agora: 'pagina', 'env' ou null. Para a página mostrar. */
    public static function origem(string $campo): ?string
    {
        try {
            if (filled(self::lerGuardado($campo))) {
                return 'pagina';
            }
        } catch (\Throwable) {
            // Não decifra: conta como não guardado.
        }

        return filled(config("moloni.{$campo}")) ? 'env' : null;
    }
}
