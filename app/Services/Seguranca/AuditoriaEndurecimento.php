<?php

namespace App\Services\Seguranca;

use App\Models\HardeningAudit;
use App\Models\Server;
use App\Services\SshService;

/**
 * Corre a auditoria de endurecimento num servidor.
 *
 * Todas as verificações vão numa única ligação SSH: monta-se um script com
 * marcas entre comandos e parte-se a saída no fim. Com dezasseis verificações
 * e nove máquinas, uma ligação por verificação seriam cento e quarenta e
 * quatro ligações — e alguns servidores têm fail2ban a olhar para isso.
 */
class AuditoriaEndurecimento
{
    private const MARCA = '@@@ATENEYA:';

    public function __construct(private SshService $ssh)
    {
    }

    public function correr(Server $server, string $lancadaPor = 'painel'): HardeningAudit
    {
        $auditoria = HardeningAudit::create([
            'server_id'   => $server->id,
            'estado'      => 'pendente',
            'lancada_por' => $lancadaPor,
            'comecou_em'  => now(),
        ]);

        $verificacoes = CatalogoEndurecimento::para($server);

        try {
            $saidas = $this->recolher($server, $verificacoes);
        } catch (\Throwable $e) {
            $auditoria->update([
                'estado'    => 'erro',
                'erro'      => $e->getMessage(),
                'acabou_em' => now(),
            ]);

            return $auditoria;
        }

        $resultados = [];

        foreach ($verificacoes as $chave => $verificacao) {
            $resultados[] = $verificacao->paraArray(
                $verificacao->avaliar($saidas[$chave] ?? '')
            );
        }

        $falhas = count(array_filter($resultados, fn ($r) => $r['estado'] === 'falha'));
        $avisos = count(array_filter($resultados, fn ($r) => $r['estado'] === 'aviso'));

        $auditoria->update([
            'estado'     => match (true) {
                $falhas > 0 => 'falhas',
                $avisos > 0 => 'avisos',
                default     => 'ok',
            },
            'total'      => count($resultados),
            'falhas'     => $falhas,
            'avisos'     => $avisos,
            'resultados' => $resultados,
            'acabou_em'  => now(),
        ]);

        return $auditoria;
    }

    /** Corre a correcção de uma verificação e volta a verificá-la. */
    public function corrigir(Server $server, string $chave): array
    {
        $verificacao = CatalogoEndurecimento::verificacao($server, $chave);

        if (! $verificacao || ! $verificacao->correcao) {
            throw new \RuntimeException('Esta verificação não tem correcção automática.');
        }

        $resposta = $this->ssh->run($server, $verificacao->correcao, timeout: 300);

        return [
            'saida'     => trim((string) $resposta['output']),
            'exit_code' => $resposta['exit_code'],
            'resultado' => $verificacao->paraArray($this->reverificar($server, $verificacao)),
        ];
    }

    public function reverificar(Server $server, Verificacao $verificacao): array
    {
        $resposta = $this->ssh->run($server, $verificacao->comando, timeout: 120);

        return $verificacao->avaliar((string) $resposta['output']);
    }

    /**
     * @param  array<string, Verificacao>  $verificacoes
     * @return array<string, string>
     */
    private function recolher(Server $server, array $verificacoes): array
    {
        $partes = ['export DEBIAN_FRONTEND=noninteractive', 'export LC_ALL=C'];

        foreach ($verificacoes as $chave => $verificacao) {
            $partes[] = 'echo "'.self::MARCA.$chave.'"';
            $partes[] = '{ '.$verificacao->comando.' ; } 2>/dev/null';
        }

        $resposta = $this->ssh->run($server, implode("\n", $partes), timeout: 180);

        return $this->separar((string) $resposta['output']);
    }

    /** @return array<string, string> */
    private function separar(string $saida): array
    {
        $blocos = [];
        $chave  = null;

        foreach (preg_split('/\R/', $saida) ?: [] as $linha) {
            if (str_starts_with($linha, self::MARCA)) {
                $chave          = trim(substr($linha, strlen(self::MARCA)));
                $blocos[$chave] = [];

                continue;
            }

            if ($chave !== null) {
                $blocos[$chave][] = $linha;
            }
        }

        return array_map(
            fn (array $linhas) => trim(implode("\n", $linhas)),
            $blocos,
        );
    }
}
