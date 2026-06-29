<?php

namespace App\Services\PurchaseInvoices;

class InvoiceItemParser
{
    public function __construct(private readonly DataNormalizer $normalizer)
    {
    }

    public function parse(string $text): array
    {
        $items = [];
        $lines = preg_split('/\R/u', $text) ?: [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line) ?? '');

            if ($line === '' || preg_match('/^(subtotal|total|total\s+a\s+pagar|valor\s+total|nif|contribuinte|fatura|factura)\b/iu', $line)) {
                continue;
            }

            if (preg_match('/^(descricao|descri..o|artigo|produto|servico|qtd|quantidade|preco|valor|iva|taxa)(\s|$)/iu', $line) && ! preg_match('/\d+[,.]\d{2}/u', $line)) {
                continue;
            }

            if (preg_match('/^(.+?)\s+(\d+(?:[,.]\d+)?)\s+(?:un|uni|und|x)?\s*(\d+(?:[.,]\d{2,4}))\s+(\d{1,2}(?:[,.]\d{1,2})?)\s*%?\s+(\d+(?:[.,]\d{2}))$/iu', $line, $m)) {
                $items[] = [
                    'description' => trim($m[1]),
                    'quantity' => $this->normalizer->money($m[2]) ?? 1,
                    'unit_price' => $this->normalizer->money($m[3]),
                    'tax_rate' => $this->normalizer->money($m[4]),
                    'tax_amount' => null,
                    'total' => $this->normalizer->money($m[5]),
                ];
                continue;
            }

            if (preg_match('/^(.+?)\s+(\d+(?:[,.]\d+)?)\s+(\d+(?:[.,]\d{2}))$/u', $line, $m)) {
                $items[] = [
                    'description' => trim($m[1]),
                    'quantity' => $this->normalizer->money($m[2]) ?? 1,
                    'unit_price' => null,
                    'tax_rate' => null,
                    'tax_amount' => null,
                    'total' => $this->normalizer->money($m[3]),
                ];
                continue;
            }

            if (preg_match('/^(.{4,}?)\s+(\d+(?:[.,]\d{2}))$/u', $line, $m)) {
                $items[] = [
                    'description' => trim($m[1]),
                    'quantity' => 1,
                    'unit_price' => $this->normalizer->money($m[2]),
                    'tax_rate' => null,
                    'tax_amount' => null,
                    'total' => $this->normalizer->money($m[2]),
                ];
            }
        }

        return array_values(array_filter($items, fn (array $item) => strlen($item['description']) >= 3));
    }
}
