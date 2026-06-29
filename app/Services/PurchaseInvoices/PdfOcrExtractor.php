<?php

namespace App\Services\PurchaseInvoices;

use Symfony\Component\Process\Process;

class PdfOcrExtractor
{
    public function __construct(
        private readonly ImageOcrExtractor $imageOcrExtractor,
        private readonly PdfTextExtractor $pdfTextExtractor,
    ) {
    }

    public function extract(string $path): string
    {
        $ocrText = $this->extractWithOcrMyPdf($path);

        if ($ocrText !== '') {
            return $ocrText;
        }

        $binary = $this->binary(config('purchase_invoices.pdftoppm_binary'), 'pdftoppm');

        if (! $binary) {
            return '';
        }

        $tmpDir = storage_path('app/private/purchase-invoice-pages/'.uniqid('pdf_', true));
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $prefix = $tmpDir.DIRECTORY_SEPARATOR.'page';
        $text = '';

        try {
            $process = new Process([$binary, '-png', '-r', '250', '-f', '1', '-l', '5', $path, $prefix]);
            $process->setTimeout(120)->run();

            if (! $process->isSuccessful()) {
                return '';
            }

            foreach (glob($tmpDir.DIRECTORY_SEPARATOR.'*.png') ?: [] as $imagePath) {
                $text .= "\n".$this->imageOcrExtractor->extract($imagePath);
            }
        } finally {
            foreach (glob($tmpDir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($tmpDir);
        }

        return trim($text);
    }

    private function extractWithOcrMyPdf(string $path): string
    {
        $binary = $this->binary(config('purchase_invoices.ocrmypdf_binary'), 'ocrmypdf');

        if (! $binary) {
            return '';
        }

        $outputPath = storage_path('app/private/purchase-invoice-ocr/'.uniqid('searchable_', true).'.pdf');

        try {
            if (! is_dir(dirname($outputPath))) {
                mkdir(dirname($outputPath), 0775, true);
            }

            $process = new Process([
                $binary,
                '--skip-text',
                '--language',
                str_replace('+', '+', config('purchase_invoices.tesseract_language', 'por+eng')),
                $path,
                $outputPath,
            ]);
            $process->setTimeout(180)->run();

            if (! $process->isSuccessful() || ! is_file($outputPath)) {
                return '';
            }

            return $this->pdfTextExtractor->extract($outputPath);
        } catch (\Throwable) {
            return '';
        } finally {
            if (is_file($outputPath)) {
                @unlink($outputPath);
            }
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
