<?php

namespace App\Jobs;

use App\Models\HardeningAudit;
use App\Models\Server;
use App\Services\Seguranca\AuditoriaEndurecimento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Auditoria de endurecimento de um servidor, em segundo plano.
 *
 * O "Auditar todos" corria as máquinas uma a uma dentro do pedido web e o
 * nginx desistia com 504 antes de acabar (25/09/2026). Agora cada servidor
 * é um job; a auditoria aparece na listagem assim que acaba.
 *
 * 01/10/2026: o "Auditar servidor" e o "Auditar outra vez" também passam por
 * aqui. Recebem a linha já criada (estado "pendente") para a página poder
 * abrir logo e ir-se refrescando; o "Auditar todos" continua a mandar só o
 * servidor e a linha é criada aqui.
 */
class CorrerAuditoriaEndurecimento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public Server $servidor,
        public string $lancadaPor = 'painel',
        public ?HardeningAudit $auditoria = null,
    ) {
    }

    public function handle(AuditoriaEndurecimento $servico): void
    {
        $this->auditoria ??= AuditoriaEndurecimento::nova($this->servidor, $this->lancadaPor);

        $servico->correrExistente($this->auditoria);
    }

    public function failed(\Throwable $e): void
    {
        $this->auditoria?->update([
            'estado'    => 'erro',
            'erro'      => $e->getMessage(),
            'acabou_em' => now(),
        ]);
    }
}
