<?php

namespace App\Services\Velocidade;

use App\Services\Seguranca\Verificacao;

/**
 * Uma verificação de velocidade. É a mesma coisa que uma de endurecimento —
 * comando que lê, forma de avaliar, comando que corrige — com mais um campo:
 * o grupo (a máquina, ou o domínio do site), para a página as mostrar
 * arrumadas por site.
 */
class VerificacaoVelocidade extends Verificacao
{
    public function __construct(
        public string $grupo,
        string $chave,
        string $label,
        string $severidade,
        string $porque,
        string $comando,
        \Closure $avaliar,
        ?string $correcao = null,
        ?string $perigo = null,
        bool $aplicavelComPlesk = true,
    ) {
        parent::__construct(
            chave: $chave,
            label: $label,
            severidade: $severidade,
            porque: $porque,
            comando: $comando,
            avaliar: $avaliar,
            correcao: $correcao,
            perigo: $perigo,
            aplicavelComPlesk: $aplicavelComPlesk,
        );
    }

    public function paraArray(array $resultado): array
    {
        return ['grupo' => $this->grupo] + parent::paraArray($resultado);
    }
}
