<?php

namespace App\Services\Contabilidade;

use App\Models\AccountingDocument;
use App\Models\MoloniDocumento;

/**
 * Vendas (Moloni) menos despesas (documentos de contabilidade), mes a mes.
 *
 * Tudo em centimos e sem IVA — o resultado e' sobre a base. O IVA vai em
 * colunas proprias: liquidado nas vendas, dedutivel nas despesas, e a
 * diferenca e' o que ha a entregar ao Estado (negativo = a recuperar).
 *
 * E' uma aproximacao de gestao, nao a contabilidade: nao ha amortizacoes,
 * salarios que nao passem por fatura, nem acrescimos. E o IVA "dedutivel" e'
 * todo o IVA das faturas de despesa — o contabilista pode nao deduzir parte
 * (refeicoes, combustivel de ligeiros...).
 */
class Resultados
{
    public const CHAVE_MAPA_SERIES = 'moloni.series_marcas';

    /** Marca escolhida no filtro: null = todas, 0 = sem marca, >0 = essa marca. */
    public static function doAno(int $ano, ?int $marca = null): array
    {
        $mapa = MoloniDocumento::mapaSeries();
        $sinais = (array) config('moloni.tipos_venda', []);

        $vendas = MoloniDocumento::query()
            ->where('ano', $ano)
            ->whereIn('tipo', array_keys($sinais))
            ->get(['mes', 'tipo', 'base_cents', 'iva_cents', 'document_set_id'])
            ->map(fn ($d) => [
                'mes'   => (int) $d->mes,
                'sinal' => (int) ($sinais[$d->tipo] ?? 0),
                'base'  => (int) $d->base_cents,
                'iva'   => (int) $d->iva_cents,
                'marca' => $mapa[(int) $d->document_set_id] ?? 0,
            ])
            ->all();

        $despesas = AccountingDocument::query()
            ->where('year', $ano)
            ->where('estado', '!=', 'por_rever')
            ->get(['month', 'tipo', 'amount_cents', 'iva_cents', 'category', 'brand_id'])
            ->map(fn ($d) => [
                'mes'       => (int) $d->month,
                'tipo'      => (string) $d->tipo,
                'total'     => (int) $d->amount_cents,
                'iva'       => (int) $d->iva_cents,
                'categoria' => (string) $d->category,
                'marca'     => (int) ($d->brand_id ?? 0),
            ])
            ->all();

        return self::calcular($vendas, $despesas, $marca);
    }

    /**
     * Calculo puro, sem base de dados — e' o que os testes exercitam.
     *
     * @param array<int,array{mes:int,sinal:int,base:int,iva:int,marca:int}> $vendas
     * @param array<int,array{mes:int,tipo:string,total:int,iva:int,categoria:string,marca:int}> $despesas
     */
    public static function calcular(array $vendas, array $despesas, ?int $marca = null): array
    {
        $vazio = ['vendas' => 0, 'iva_liquidado' => 0, 'n_vendas' => 0, 'despesas' => 0, 'iva_dedutivel' => 0, 'n_despesas' => 0];
        $meses = array_fill(1, 12, $vazio);
        $categorias = [];

        foreach ($vendas as $v) {
            if ($v['sinal'] === 0 || ! self::daMarca($v['marca'], $marca) || ! isset($meses[$v['mes']])) {
                continue;
            }

            $meses[$v['mes']]['vendas'] += $v['sinal'] * $v['base'];
            $meses[$v['mes']]['iva_liquidado'] += $v['sinal'] * $v['iva'];
            $meses[$v['mes']]['n_vendas']++;
        }

        foreach ($despesas as $d) {
            // Recibos pagam uma fatura que ja' esta' contada.
            $sinal = match ($d['tipo']) {
                'recibo'       => 0,
                'nota_credito' => -1,
                default        => 1,
            };

            if ($sinal === 0 || ! self::daMarca($d['marca'], $marca) || ! isset($meses[$d['mes']])) {
                continue;
            }

            $base = $sinal * ($d['total'] - $d['iva']);
            $meses[$d['mes']]['despesas'] += $base;
            $meses[$d['mes']]['iva_dedutivel'] += $sinal * $d['iva'];
            $meses[$d['mes']]['n_despesas']++;

            $cat = $d['categoria'] !== '' ? $d['categoria'] : 'outros';
            $categorias[$cat] = ($categorias[$cat] ?? 0) + $base;
        }

        $total = $vazio;
        foreach ($meses as $m => $linha) {
            foreach ($vazio as $k => $_) {
                $total[$k] += $linha[$k];
            }
            $meses[$m]['resultado'] = $linha['vendas'] - $linha['despesas'];
            $meses[$m]['iva_a_entregar'] = $linha['iva_liquidado'] - $linha['iva_dedutivel'];
        }
        $total['resultado'] = $total['vendas'] - $total['despesas'];
        $total['iva_a_entregar'] = $total['iva_liquidado'] - $total['iva_dedutivel'];

        arsort($categorias);

        return ['meses' => $meses, 'total' => $total, 'categorias' => $categorias];
    }

    private static function daMarca(int $marcaDoRegisto, ?int $filtro): bool
    {
        return $filtro === null || $marcaDoRegisto === $filtro;
    }
}
