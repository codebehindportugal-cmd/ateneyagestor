<?php

namespace App\Models;

/**
 * Auditoria de velocidade. Funciona exactamente como a de endurecimento
 * (estados, contagens, substituir uma linha depois de corrigir) — só muda a
 * tabela e o catálogo de verificações.
 */
class SpeedAudit extends HardeningAudit
{
    protected $table = 'speed_audits';

    /** Resultados arrumados por grupo: "Máquina" primeiro, depois cada site. */
    public function porGrupo(): array
    {
        $grupos = [];
        foreach ($this->resultados ?? [] as $r) {
            $grupos[$r['grupo'] ?? 'Máquina'][] = $r;
        }

        uksort($grupos, fn ($a, $b) => $a === 'Máquina' ? -1 : ($b === 'Máquina' ? 1 : strcmp($a, $b)));

        return $grupos;
    }
}
