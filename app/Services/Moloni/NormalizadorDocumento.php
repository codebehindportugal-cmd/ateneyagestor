<?php

namespace App\Services\Moloni;

use Carbon\CarbonImmutable;

/**
 * Converte um documento do documents/getAll numa linha da moloni_documentos.
 *
 * Separado do sincronizador para se poder testar sem base de dados nem rede.
 */
class NormalizadorDocumento
{
    /** @return array<string,mixed>|null  null = documento sem data ou sem id */
    public static function linha(array $d, int $empresaId): ?array
    {
        $id = (int) ($d['document_id'] ?? 0);
        $data = self::data($d['date'] ?? null);

        if ($id <= 0 || $data === null) {
            return null;
        }

        [$base, $iva, $total] = self::valores($d);

        return [
            'moloni_document_id' => $id,
            'company_id'         => $empresaId,
            'tipo'               => strtoupper((string) ($d['document_type']['saft_code'] ?? $d['saft_code'] ?? '')),
            'document_set_id'    => ($d['document_set_id'] ?? $d['document_set']['document_set_id'] ?? null) ?: null,
            'serie'              => $d['document_set']['name'] ?? ($d['document_set_name'] ?? null),
            'numero'             => ($d['number'] ?? null) !== null ? (int) $d['number'] : null,
            'data'               => $data->toDateString(),
            'ano'                => $data->year,
            'mes'                => $data->month,
            'cliente'            => self::texto($d['entity_name'] ?? null),
            'cliente_nif'        => self::texto($d['entity_vat'] ?? null),
            'base_cents'         => $base,
            'iva_cents'          => $iva,
            'total_cents'        => $total,
            'estado'             => (int) ($d['status'] ?? 1),
        ];
    }

    /**
     * [base sem IVA, IVA, total com IVA], em centimos e sempre positivos.
     *
     * No Moloni o `net_value` e' o total a pagar (com IVA) e o `taxes_value`
     * e' o IVA. Confirma-se contra `gross_value - descontos`, que e' a base:
     * se o net bater com a base e nao com base + IVA, entao o net veio sem
     * IVA e o total e' net + IVA. Assim uma diferenca de leitura do campo nao
     * poe o IVA a contar duas vezes (ou nenhuma) nos Resultados.
     *
     * @return array{0:int,1:int,2:int}
     */
    public static function valores(array $d): array
    {
        $c = fn ($v) => (int) round(((float) $v) * 100);

        $bruto    = $c($d['gross_value'] ?? 0);
        $descontos = $c($d['comercial_discount_value'] ?? 0) + $c($d['financial_discount_value'] ?? 0);
        $iva      = abs($c($d['taxes_value'] ?? 0));
        $net      = abs($c($d['net_value'] ?? 0));

        $baseEsperada = abs($bruto - $descontos);

        if ($bruto > 0 && abs($net - $baseEsperada) <= 2 && abs(($net - $iva) - $baseEsperada) > 2) {
            // net veio sem IVA
            return [$net, $iva, $net + $iva];
        }

        return [max(0, $net - $iva), $iva, $net];
    }

    private static function data(mixed $valor): ?CarbonImmutable
    {
        if (blank($valor)) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $valor);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function texto(mixed $v): ?string
    {
        $v = trim((string) $v);

        return $v === '' ? null : mb_substr($v, 0, 255);
    }
}
