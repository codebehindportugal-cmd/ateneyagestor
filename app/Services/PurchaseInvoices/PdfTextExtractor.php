<?php

namespace App\Services\PurchaseInvoices;

use Spatie\PdfToText\Pdf;

class PdfTextExtractor
{
    public function extract(string $path): string
    {
        $binary = $this->binary(config('purchase_invoices.pdftotext_binary'), 'pdftotext');

        if (! $binary) {
            return '';
        }

        try {
            return Pdf::getText($path, $binary, ['layout'], 60);
        } catch (\Throwable) {
            return '';
        }
    }

    private function binary(?string $configured, string $command): ?string
    {
        if ($configured && is_file($configured)) {
            return $configured;
        }

        $local = base_path('bin/poppler/Library/bin/'.$command.(PHP_OS_FAMILY === 'Windows' ? '.exe' : ''));
        if (is_file($local)) {
            return $local;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return null;
        }

        return $configured ?: $command;
    }
}
