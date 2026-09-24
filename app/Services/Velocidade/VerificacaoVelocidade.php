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

    /**
     * Igual ao da classe-mãe, mas deixa passar 'semCorrecao': quando o que
     * falta já não é coisa que o botão «Corrigir» resolva (ex.: só subir a
     * versão do PHP), a linha fica sem botão em vez de o oferecer em vão.
     */
    public function avaliar(string $saida): array
    {
        $r = ($this->avaliar)(trim($saida));

        return [
            'estado'      => $r['estado'] ?? 'aviso',
            'detalhe'     => $r['detalhe'] ?? '',
            'semCorrecao' => (bool) ($r['semCorrecao'] ?? false),
        ];
    }

    public function paraArray(array $resultado): array
    {
        $linha = ['grupo' => $this->grupo] + parent::paraArray($resultado);
        if (! empty($resultado['semCorrecao'])) {
            $linha['correcao'] = null;
        }

        return $linha;
    }
}
