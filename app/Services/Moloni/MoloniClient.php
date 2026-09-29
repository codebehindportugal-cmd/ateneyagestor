<?php

namespace App\Services\Moloni;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Cliente minimo da API do Moloni (v1), so' de leitura.
 *
 * Autenticacao pelo "password grant": o access_token dura 1 hora e o
 * refresh_token 14 dias. Os dois ficam na cache — assim a sincronizacao de
 * duas em duas horas renova com o refresh em vez de mandar a password ao
 * Moloni a cada corrida.
 */
class MoloniClient
{
    private const CHAVE_CACHE = 'moloni.tokens';

    public function __construct(private readonly array $config = [])
    {
    }

    public static function daConfig(): self
    {
        return new self((array) config('moloni'));
    }

    public function configurado(): bool
    {
        foreach (['client_id', 'client_secret', 'username', 'password'] as $campo) {
            if (blank($this->config[$campo] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /** @return array<int,array<string,mixed>> */
    public function empresas(): array
    {
        return $this->chamar('companies/getAll');
    }

    public function empresaId(): int
    {
        if (! empty($this->config['company_id'])) {
            return (int) $this->config['company_id'];
        }

        $empresas = $this->empresas();

        if ($empresas === [] || empty($empresas[0]['company_id'])) {
            throw new MoloniException('A conta do Moloni nao tem nenhuma empresa associada.');
        }

        return (int) $empresas[0]['company_id'];
    }

    /** @return array<int,array<string,mixed>> */
    public function series(int $empresaId): array
    {
        return $this->chamar('documentSets/getAll', ['company_id' => $empresaId]);
    }

    /**
     * Todos os documentos fechados de um ano, de 50 em 50 (o maximo do Moloni).
     *
     * @return \Generator<int,array<string,mixed>>
     */
    public function documentosDoAno(int $empresaId, int $ano): \Generator
    {
        $offset = 0;

        do {
            $pagina = $this->chamar('documents/getAll', [
                'company_id' => $empresaId,
                'year'       => $ano,
                'status'     => 1,   // 1 = fechado; os rascunhos nao sao vendas
                'qty'        => 50,
                'offset'     => $offset,
            ]);

            foreach ($pagina as $documento) {
                yield $documento;
            }

            $offset += 50;
        } while (count($pagina) === 50);
    }

    /**
     * @return array<int|string,mixed>
     */
    public function chamar(string $endpoint, array $parametros = []): array
    {
        if (! $this->configurado()) {
            throw new MoloniException('Faltam as credenciais do Moloni no .env (MOLONI_CLIENT_ID, MOLONI_CLIENT_SECRET, MOLONI_USERNAME, MOLONI_PASSWORD).');
        }

        $resposta = $this->pedido($endpoint, $parametros, $this->accessToken());

        // Token recusado a meio (revogado, ou a cache enganou-se na validade):
        // pede um novo uma vez e repete.
        if ($this->tokenRecusado($resposta)) {
            Cache::forget(self::CHAVE_CACHE);
            $resposta = $this->pedido($endpoint, $parametros, $this->accessToken());
        }

        $dados = $resposta->json();

        if (! $resposta->successful() || ! is_array($dados)) {
            throw new MoloniException("Moloni {$endpoint}: HTTP {$resposta->status()} — ".mb_substr((string) $resposta->body(), 0, 300));
        }

        if (isset($dados['error'])) {
            throw new MoloniException("Moloni {$endpoint}: ".($dados['error_description'] ?? $dados['error']));
        }

        return $dados;
    }

    private function pedido(string $endpoint, array $parametros, string $token): \Illuminate\Http\Client\Response
    {
        $url = $this->config['base_url'].'/'.trim($endpoint, '/').'/?access_token='.urlencode($token).'&json=true';

        return Http::timeout((int) ($this->config['timeout'] ?? 30))
            ->acceptJson()
            ->asJson()
            ->post($url, $parametros);
    }

    private function tokenRecusado(\Illuminate\Http\Client\Response $resposta): bool
    {
        if ($resposta->status() === 401) {
            return true;
        }

        $erro = (string) ($resposta->json('error') ?? '');

        return in_array($erro, ['invalid_token', 'expired_token', 'invalid_grant'], true);
    }

    private function accessToken(): string
    {
        $tokens = Cache::get(self::CHAVE_CACHE);

        if (is_array($tokens) && ($tokens['expira_em'] ?? 0) > time() + 60) {
            return $tokens['access_token'];
        }

        if (is_array($tokens) && ! empty($tokens['refresh_token']) && ($tokens['refresh_expira_em'] ?? 0) > time() + 60) {
            try {
                return $this->guardarTokens($this->grant([
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $tokens['refresh_token'],
                ]));
            } catch (MoloniException) {
                // refresh recusado: cai para a password
            }
        }

        return $this->guardarTokens($this->grant([
            'grant_type' => 'password',
            'username'   => $this->config['username'],
            'password'   => $this->config['password'],
        ]));
    }

    private function grant(array $parametros): array
    {
        $parametros += [
            'client_id'     => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
        ];

        $resposta = Http::timeout((int) ($this->config['timeout'] ?? 30))
            ->acceptJson()
            ->get($this->config['base_url'].'/grant/', $parametros);

        $dados = $resposta->json();

        if (! $resposta->successful() || ! is_array($dados) || empty($dados['access_token'])) {
            $motivo = is_array($dados) ? ($dados['error_description'] ?? $dados['error'] ?? '') : '';
            throw new MoloniException('O Moloni recusou a autenticacao'.($motivo ? ": {$motivo}" : " (HTTP {$resposta->status()})").'.');
        }

        return $dados;
    }

    private function guardarTokens(array $dados): string
    {
        $expira = (int) ($dados['expires_in'] ?? 3600);

        Cache::put(self::CHAVE_CACHE, [
            'access_token'      => $dados['access_token'],
            'expira_em'         => time() + $expira,
            'refresh_token'     => $dados['refresh_token'] ?? null,
            'refresh_expira_em' => time() + 14 * 86400,
        ], now()->addDays(14));

        return $dados['access_token'];
    }
}
