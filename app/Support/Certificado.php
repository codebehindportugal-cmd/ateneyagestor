<?php

namespace App\Support;

/**
 * Le o certificado TLS de um site e diz o que esta mal com ele.
 *
 * O monitor dos sites pede as paginas com withoutVerifying() de proposito —
 * um certificado mau nao e "site em baixo". O custo disso (28/09/2026): um
 * certificado expirado, ou o certificado por omissao do Plesk num dominio que
 * nunca teve SSL (jornadascinegeticas.pt), passava sempre como "Online".
 * Isto preenche esse buraco uma vez por dia, no resumo das 08:00.
 */
class Certificado
{
    /**
     * @return array{host: string, dias: ?int, problema: ?string}|null
     *         null quando o URL nao e https (nao ha certificado a ver).
     */
    public static function verificar(string $url, int $timeout = 8): ?array
    {
        $partes = parse_url($url);

        if (($partes['scheme'] ?? '') !== 'https' || empty($partes['host'])) {
            return null;
        }

        $host  = strtolower($partes['host']);
        $porta = (int) ($partes['port'] ?? 443);

        $contexto = stream_context_create(['ssl' => [
            'capture_peer_cert' => true,
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'SNI_enabled'       => true,
            'peer_name'         => $host,
        ]]);

        $ligacao = @stream_socket_client(
            "ssl://{$host}:{$porta}",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $contexto,
        );

        if (! $ligacao) {
            return ['host' => $host, 'dias' => null, 'problema' => 'Sem ligacao TLS: ' . ($errstr ?: "erro {$errno}")];
        }

        $params = stream_context_get_params($ligacao);
        fclose($ligacao);

        $cert  = $params['options']['ssl']['peer_certificate'] ?? null;
        $dados = $cert ? openssl_x509_parse($cert) : false;

        if (! $dados) {
            return ['host' => $host, 'dias' => null, 'problema' => 'Certificado ilegivel'];
        }

        $dias = (int) floor(((int) ($dados['validTo_time_t'] ?? 0) - time()) / 86400);

        $problema = match (true) {
            $dias < 0                                   => 'Expirou ha ' . abs($dias) . ' dia(s)',
            ! self::cobre($host, $dados)                => 'Nao e deste dominio (' . ($dados['subject']['CN'] ?? '?') . ')',
            self::autoAssinado($dados)                  => 'Autoassinado',
            default                                     => null,
        };

        return ['host' => $host, 'dias' => $dias, 'problema' => $problema];
    }

    /** O certificado cobre este host? Olha para o SAN e, sem SAN, para o CN. */
    private static function cobre(string $host, array $dados): bool
    {
        $nomes = [];

        foreach (explode(',', (string) ($dados['extensions']['subjectAltName'] ?? '')) as $entrada) {
            $entrada = trim($entrada);
            if (str_starts_with($entrada, 'DNS:')) {
                $nomes[] = strtolower(substr($entrada, 4));
            }
        }

        if (! $nomes && ! empty($dados['subject']['CN'])) {
            $nomes[] = strtolower((string) $dados['subject']['CN']);
        }

        foreach ($nomes as $nome) {
            if ($nome === $host) {
                return true;
            }

            // *.exemplo.pt cobre www.exemplo.pt, mas nao exemplo.pt nem a.b.exemplo.pt
            if (str_starts_with($nome, '*.')) {
                $base = substr($nome, 1); // ".exemplo.pt"
                if (str_ends_with($host, $base) && substr_count(substr($host, 0, -strlen($base)), '.') === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function autoAssinado(array $dados): bool
    {
        return ($dados['subject'] ?? null) === ($dados['issuer'] ?? null);
    }
}
