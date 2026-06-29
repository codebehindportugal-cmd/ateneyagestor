<?php

namespace App\Services\PurchaseInvoices;

class InvoiceParser
{
    public function __construct(
        private readonly DataNormalizer $normalizer,
        private readonly InvoiceItemParser $itemParser,
    ) {
    }

    public function parse(string $text): array
    {
        $lines = collect(preg_split('/\R/u', $text) ?: [])
            ->map(fn (string $line) => trim(preg_replace('/\s+/u', ' ', $line) ?? ''))
            ->filter()
            ->values()
            ->all();

        $data = [
            'supplier_name' => $this->supplierName($lines),
            'supplier_tax_number' => $this->firstMatch($text, [
                '/(?:NIF|NIPC|VAT|Contribuinte|Numero de contribuinte|N\.?\s*Fiscal)\D*(\d{9})/iu',
            ]),
            'invoice_number' => $this->firstMatch($text, [
                '/(?:Fatura|Factura|Invoice|Documento|Doc\.?)\s*(?:n\.?|no|n(?:umero)?|#)?\s*[:\-]?\s*([A-Z0-9][A-Z0-9\/\-. ]{2,40})/iu',
                '/\b((?:FT|FS|FR|FAC|NC|ND)\s*[A-Z0-9\/\-. ]{3,40})/iu',
            ]),
            'invoice_date' => $this->normalizer->date($this->firstMatch($text, [
                '/(?:Data de emissao|Data emissao|Emitido em|Data)\D*(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4})/iu',
                '/\b(\d{4}-\d{2}-\d{2})\b/u',
            ])),
            'due_date' => $this->normalizer->date($this->firstMatch($text, [
                '/(?:Vencimento|Data de vencimento|Vence em)\D*(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4})/iu',
            ])),
            'subtotal' => $this->moneyAfter($text, ['subtotal', 'base tributavel', 'total s/ iva', 'valor liquido']),
            'tax_total' => $this->moneyAfter($text, ['total iva', 'iva total', 'imposto']),
            'total' => $this->moneyAfter($text, ['total a pagar', 'valor total', 'total documento', 'total']),
            'currency' => preg_match('/\b(USD|GBP|EUR)\b|\$/u', $text, $m) ? ($m[1] ?: 'USD') : 'EUR',
        ];

        $items = $this->itemParser->parse($text);

        return $data + [
            'items' => $items,
            'confidence' => [
                'supplier_name' => $data['supplier_name'] ? 0.5 : 0,
                'supplier_tax_number' => $data['supplier_tax_number'] ? 0.8 : 0,
                'invoice_number' => $data['invoice_number'] ? 0.7 : 0,
                'invoice_date' => $data['invoice_date'] ? 0.7 : 0,
                'due_date' => $data['due_date'] ? 0.6 : 0,
                'subtotal' => $data['subtotal'] !== null ? 0.6 : 0,
                'tax_total' => $data['tax_total'] !== null ? 0.6 : 0,
                'total' => $data['total'] !== null ? 0.8 : 0,
                'items' => $items !== [] ? 0.45 : 0,
            ],
        ];
    }

    private function supplierName(array $lines): ?string
    {
        foreach (array_slice($lines, 0, 8) as $line) {
            if (! preg_match('/(fatura|factura|invoice|nif|contribuinte|data|original|duplicado)/iu', $line) && mb_strlen($line) > 3) {
                return mb_substr($line, 0, 255);
            }
        }

        return null;
    }

    private function firstMatch(string $text, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1] ?? $matches[0]);
            }
        }

        return null;
    }

    private function moneyAfter(string $text, array $labels): ?float
    {
        foreach ($labels as $label) {
            $quoted = preg_quote($label, '/');
            if (preg_match_all('/'.$quoted.'\D{0,25}(\d{1,6}(?:[.\s]\d{3})*(?:[,.]\d{2}))/iu', $text, $matches)) {
                return $this->normalizer->money(end($matches[1]));
            }
        }

        return null;
    }
}
