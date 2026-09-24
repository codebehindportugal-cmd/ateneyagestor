<?php

namespace App\Jobs;

use App\Models\SpeedAudit;
use App\Services\Velocidade\AuditoriaVelocidade;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Uma correcção de velocidade corre na fila, não no pedido web.
 *
 * Instalar pacotes ou aquecer a cache de um site passa do minuto, e o proxy do
 * Plesk corta o pedido aos 60 s: a correcção corria até ao fim mas ninguém via
 * o resultado. Agora a saída completa fica guardada na própria linha da
 * auditoria (campo "saida") e a página mostra-a.
 */
class CorrigirVelocidade implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public SpeedAudit $auditoria, public string $chave)
    {
    }

    public function handle(AuditoriaVelocidade $servico): void
    {
        $antes = $this->auditoria->resultado($this->chave) ?? [];

        try {
            $r = $servico->corrigir($this->auditoria->server, $this->chave);
            $novo = $r['resultado'] + [
                'saida'        => mb_substr($r['saida'], -6000),
                'saida_codigo' => $r['exit_code'],
                'corrigido_em' => now()->toDateTimeString(),
            ];
        } catch (\Throwable $e) {
            $novo = ['a_correr' => false, 'saida' => 'Erro: ' . $e->getMessage(), 'corrigido_em' => now()->toDateTimeString()] + $antes;
            unset($novo['a_correr']);
        }

        $this->auditoria->refresh();
        $this->auditoria->substituirResultado($this->chave, $novo);
    }

    public function failed(\Throwable $e): void
    {
        $antes = $this->auditoria->fresh()->resultado($this->chave) ?? [];
        unset($antes['a_correr']);
        $this->auditoria->substituirResultado($this->chave, ['saida' => 'Falhou: ' . $e->getMessage()] + $antes);
    }
}
