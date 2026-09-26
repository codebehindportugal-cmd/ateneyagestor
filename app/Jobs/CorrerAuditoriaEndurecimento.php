<?php

namespace App\Jobs;

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
 */
class CorrerAuditoriaEndurecimento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public Server $servidor, public string $lancadaPor = 'painel')
    {
    }

    public function handle(AuditoriaEndurecimento $servico): void
    {
        $servico->correr($this->servidor, $this->lancadaPor);
    }
}
