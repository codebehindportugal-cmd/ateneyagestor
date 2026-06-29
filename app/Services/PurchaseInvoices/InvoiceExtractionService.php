<?php

namespace App\Services\PurchaseInvoices;

class InvoiceExtractionService
{
    public function __construct(
        private readonly PdfTextExtractor $pdfTextExtractor,
        private readonly PdfOcrExtractor $pdfOcrExtractor,
        private readonly ImageOcrExtractor $imageOcrExtractor,
        private readonly InvoiceParser $invoiceParser,
    ) {
    }

    public function extract(string $path, string $mimeType, array $extraPaths = []): array
    {
        $rawText = str_contains($mimeType, 'pdf')
            ? $this->extractPdf($path)
            : $this->imageOcrExtractor->extract($path);

        foreach ($extraPaths as $extraPath) {
            $extraMimeType = is_file($extraPath) ? ((string) mime_content_type($extraPath)) : '';
            $rawText .= "\n\n".(str_contains($extraMimeType, 'pdf')
                ? $this->extractPdf($extraPath)
                : $this->imageOcrExtractor->extract($extraPath));
        }

        $parsed = $this->invoiceParser->parse($rawText);

        return $parsed + ['raw_text' => trim($rawText)];
    }

    private function extractPdf(string $path): string
    {
        $text = $this->pdfTextExtractor->extract($path);

        if (mb_strlen(preg_replace('/\s+/u', '', $text) ?? '') >= (int) config('purchase_invoices.min_pdf_text_length', 100)) {
            return $text;
        }

        return $this->pdfOcrExtractor->extract($path) ?: $text;
    }
}
