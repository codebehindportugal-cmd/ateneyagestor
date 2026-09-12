<?php

namespace App\Services\Seguranca;

/**
 * Uma verificação de endurecimento: o que se pergunta ao servidor, como se lê
 * a resposta, e — quando existe — o comando que corrige.
 *
 * As correcções são sempre comandos completos e mostrados ao utilizador antes
 * de correrem. Nada nesta classe corre sozinho.
 */
class Verificacao
{
    public function __construct(
        public string $chave,
        public string $label,
        /** critica | importante | info */
        public string $severidade,
        /** Uma frase: porque é que isto interessa. */
        public string $porque,
        /** O comando que recolhe o estado (stdout+stderr já tratados no catálogo). */
        public string $comando,
        /** fn (string $saida): array{estado: 'ok'|'aviso'|'falha', detalhe: string} */
        public \Closure $avaliar,
        /** O comando que corrige, ou null quando é só para ver. */
        public ?string $correcao = null,
        /** Aviso a mostrar antes de correr a correcção. */
        public ?string $perigo = null,
        /** Em servidores com Plesk a configuração é do painel: não se mexe. */
        public bool $aplicavelComPlesk = true,
    ) {
    }

    public function avaliar(string $saida): array
    {
        $r = ($this->avaliar)(trim($saida));

        return [
            'estado'  => $r['estado'] ?? 'aviso',
            'detalhe' => $r['detalhe'] ?? '',
        ];
    }

    public function paraArray(array $resultado): array
    {
        return [
            'chave'      => $this->chave,
            'label'      => $this->label,
            'severidade' => $this->severidade,
            'porque'     => $this->porque,
            'estado'     => $resultado['estado'],
            'detalhe'    => $resultado['detalhe'],
            'correcao'   => $this->correcao,
            'perigo'     => $this->perigo,
        ];
    }

    public static function severidadeLabels(): array
    {
        return [
            'critica'    => 'Crítica',
            'importante' => 'Importante',
            'info'       => 'Informação',
        ];
    }

    public static function severidadeCores(): array
    {
        return [
            'critica'    => 'danger',
            'importante' => 'warning',
            'info'       => 'gray',
        ];
    }
}
