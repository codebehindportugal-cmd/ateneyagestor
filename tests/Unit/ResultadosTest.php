<?php

namespace Tests\Unit;

use App\Services\Contabilidade\Resultados;
use App\Services\Moloni\NormalizadorDocumento;
use PHPUnit\Framework\TestCase;

class ResultadosTest extends TestCase
{
    public function test_vendas_menos_despesas_sem_iva_e_com_notas_de_credito(): void
    {
        $vendas = [
            ['mes' => 9, 'sinal' => 1,  'base' => 100000, 'iva' => 23000, 'marca' => 1],
            ['mes' => 9, 'sinal' => -1, 'base' => 10000,  'iva' => 2300,  'marca' => 1],   // NC
        ];
        $despesas = [
            ['mes' => 9, 'tipo' => 'fatura',       'total' => 12300, 'iva' => 2300, 'categoria' => 'software',    'marca' => 1],
            ['mes' => 9, 'tipo' => 'nota_credito', 'total' => 1230,  'iva' => 230,  'categoria' => 'software',    'marca' => 1],
            ['mes' => 9, 'tipo' => 'recibo',       'total' => 12300, 'iva' => 2300, 'categoria' => 'software',    'marca' => 1],
        ];

        $r = Resultados::calcular($vendas, $despesas);
        $set = $r['meses'][9];

        $this->assertSame(90000, $set['vendas']);          // 1000 - 100
        $this->assertSame(20700, $set['iva_liquidado']);
        $this->assertSame(9000, $set['despesas']);         // 100 - 10, o recibo nao conta
        $this->assertSame(2070, $set['iva_dedutivel']);
        $this->assertSame(81000, $set['resultado']);
        $this->assertSame(18630, $set['iva_a_entregar']);
        $this->assertSame(81000, $r['total']['resultado']);
        $this->assertSame(0, $r['meses'][8]['resultado']);
    }

    public function test_filtro_por_marca_e_sem_marca(): void
    {
        $vendas = [
            ['mes' => 1, 'sinal' => 1, 'base' => 5000, 'iva' => 300, 'marca' => 2],
            ['mes' => 1, 'sinal' => 1, 'base' => 7000, 'iva' => 0,   'marca' => 0],
        ];
        $despesas = [
            ['mes' => 1, 'tipo' => 'fatura', 'total' => 1060, 'iva' => 60, 'categoria' => 'mercadorias', 'marca' => 2],
            ['mes' => 1, 'tipo' => 'fatura', 'total' => 500,  'iva' => 0,  'categoria' => '',            'marca' => 1],
        ];

        $this->assertSame(4000, Resultados::calcular($vendas, $despesas, 2)['total']['resultado']);
        $this->assertSame(7000, Resultados::calcular($vendas, $despesas, 0)['total']['resultado']);
        $this->assertSame(10500, Resultados::calcular($vendas, $despesas)['total']['resultado']);
        $this->assertSame(['mercadorias' => 1000, 'outros' => 500], Resultados::calcular($vendas, $despesas)['categorias']);
    }

    public function test_normalizador_net_com_iva(): void
    {
        $linha = NormalizadorDocumento::linha([
            'document_id' => 77, 'number' => 12, 'date' => '2026-09-15T00:00:00+0100', 'status' => 1,
            'document_set_id' => 5, 'document_set' => ['document_set_id' => 5, 'name' => 'HDM'],
            'document_type' => ['saft_code' => 'FT'],
            'gross_value' => 100, 'comercial_discount_value' => 0, 'financial_discount_value' => 0,
            'taxes_value' => 6, 'net_value' => 106, 'entity_name' => 'Cliente', 'entity_vat' => '123456789',
        ], 9);

        $this->assertSame(10000, $linha['base_cents']);
        $this->assertSame(600, $linha['iva_cents']);
        $this->assertSame(10600, $linha['total_cents']);
        $this->assertSame('FT', $linha['tipo']);
        $this->assertSame(9, $linha['mes']);
        $this->assertSame('HDM', $linha['serie']);
    }

    public function test_normalizador_net_sem_iva(): void
    {
        [$base, $iva, $total] = NormalizadorDocumento::valores([
            'gross_value' => 200, 'comercial_discount_value' => 20, 'taxes_value' => 41.4, 'net_value' => 180,
        ]);

        $this->assertSame([18000, 4140, 22140], [$base, $iva, $total]);
    }
}
