<?php

namespace App\Services\Velocidade;

use App\Models\Server;
use App\Models\SpeedAudit;
use App\Services\SshService;

/**
 * Corre a auditoria de velocidade num servidor: uma só ligação SSH para todas
 * as verificações (máquina + cada site), partida no fim pelas marcas — o mesmo
 * esquema da AuditoriaEndurecimento.
 */
class AuditoriaVelocidade
{
    private const MARCA = '@@@ATENEYA:';

    public function __construct(private SshService $ssh)
    {
    }

    public static function nova(Server $server, string $lancadaPor = 'painel'): SpeedAudit
    {
        return SpeedAudit::create([
            'server_id'   => $server->id,
            'estado'      => 'pendente',
            'lancada_por' => $lancadaPor,
            'comecou_em'  => now(),
        ]);
    }

    public function correr(SpeedAudit $auditoria): SpeedAudit
    {
        $server = $auditoria->server;
        $verificacoes = CatalogoVelocidade::para($server);

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
            $resultados[] = $verificacao->paraArray($verificacao->avaliar($saidas[$chave] ?? ''));
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
        $verificacao = CatalogoVelocidade::verificacao($server, $chave);

        if (! $verificacao || ! $verificacao->correcao) {
            throw new \RuntimeException('Esta verificação não tem correcção automática.');
        }

        $resposta = $this->ssh->run($server, "exec </dev/null\nexport DEBIAN_FRONTEND=noninteractive LC_ALL=C\n{\n" . $verificacao->correcao . "\n} 2>&1", timeout: 600);

        $novo = $verificacao->avaliar((string) $this->ssh->run($server, $verificacao->comando, timeout: 180)['output']);

        return [
            'saida'     => trim((string) $resposta['output']),
            'exit_code' => $resposta['exit_code'],
            'resultado' => $verificacao->paraArray($novo),
        ];
    }

    /**
     * @param  array<string, VerificacaoVelocidade>  $verificacoes
     * @return array<string, string>
     */
    private function recolher(Server $server, array $verificacoes): array
    {
        // stdin fechado: nenhum comando pode ficar pendurado à espera de input.
        $partes = ['exec </dev/null', 'export DEBIAN_FRONTEND=noninteractive', 'export LC_ALL=C'];

        foreach ($verificacoes as $chave => $verificacao) {
            $partes[] = 'echo "' . self::MARCA . $chave . '"';
            $partes[] = '{ ' . $verificacao->comando . "\n} 2>/dev/null";
        }

        $resposta = $this->ssh->run($server, implode("\n", $partes), timeout: 840);

        return $this->separar((string) $resposta['output']);
    }

    /** @return array<string, string> */
    private function separar(string $saida): array
    {
        $blocos = [];
        $chave = null;

        foreach (preg_split('/\R/', $saida) ?: [] as $linha) {
            if (str_starts_with($linha, self::MARCA)) {
                $chave = trim(substr($linha, strlen(self::MARCA)));
                $blocos[$chave] = [];

                continue;
            }

            if ($chave !== null) {
                $blocos[$chave][] = $linha;
            }
        }

        return array_map(fn (array $linhas) => trim(implode("\n", $linhas)), $blocos);
    }
}
