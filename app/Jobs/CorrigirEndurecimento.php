<?php

namespace App\Jobs;

use App\Models\HardeningAudit;
use App\Services\Seguranca\AuditoriaEndurecimento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Uma correcção de endurecimento corre na fila, não no pedido web.
 *
 * 01/10/2026: o "Corrigir" das actualizações de segurança no liberne correu
 * o apt-get upgrade dentro do pedido e o proxy cortou-o com 504 — sem se
 * saber se o upgrade tinha acabado. Igual ao CorrigirVelocidade: a linha fica
 * marcada "a corrigir", a página refresca-se e a saída completa fica guardada.
 */
class CorrigirEndurecimento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public HardeningAudit $auditoria, public string $chave)
    {
    }

    public function handle(AuditoriaEndurecimento $servico): void
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
            $novo = ['saida' => 'Erro: '.$e->getMessage(), 'corrigido_em' => now()->toDateTimeString()] + $antes;
            unset($novo['a_correr']);
        }

        $this->auditoria->refresh();
        $this->auditoria->substituirResultado($this->chave, $novo);
    }

    public function failed(\Throwable $e): void
    {
        $antes = $this->auditoria->fresh()->resultado($this->chave) ?? [];
        unset($antes['a_correr']);
        $this->auditoria->substituirResultado($this->chave, [
            'saida'        => 'Falhou: '.$e->getMessage(),
            'corrigido_em' => now()->toDateTimeString(),
        ] + $antes);
    }
}
