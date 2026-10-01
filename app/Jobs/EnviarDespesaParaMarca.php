<?php

namespace App\Jobs;

use App\Models\AccountingDocument;
use App\Services\Contabilidade\EnvioDespesaMarca;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Leva um documento ao painel da marca (01/10/2026). Na fila para o painel
 * não ficar à espera da gestao.hortadamaria.com, e para tentar outra vez se
 * ela estiver em baixo — do outro lado o mesmo documento não entra duas vezes.
 */
class EnviarDespesaParaMarca implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public int $timeout = 120;

    /** 1 min, 10 min, 1 h. */
    public array $backoff = [60, 600, 3600];

    public function __construct(public AccountingDocument $documento)
    {
    }

    public function handle(EnvioDespesaMarca $envio): void
    {
        $doc = $this->documento->fresh(['brand']);

        // Entretanto foi enviado, voltou a "por rever" ou mudou de marca.
        if ($doc === null || ! EnvioDespesaMarca::deveSeguir($doc)) {
            return;
        }

        $envio->enviar($doc);
    }
}
