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
 * A auditoria de velocidade vai para a fila: com oito sites WordPress, cada um
 * a arrancar o WP-CLI várias vezes, passa à vontade do tempo de um pedido web.
 * A página da auditoria actualiza-se sozinha enquanto isto corre.
 */
class CorrerAuditoriaVelocidade implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public SpeedAudit $auditoria)
    {
    }

    public function handle(AuditoriaVelocidade $servico): void
    {
        $servico->correr($this->auditoria);
    }

    public function failed(\Throwable $e): void
    {
        $this->auditoria->update([
            'estado'    => 'erro',
            'erro'      => $e->getMessage(),
            'acabou_em' => now(),
        ]);
    }
}
