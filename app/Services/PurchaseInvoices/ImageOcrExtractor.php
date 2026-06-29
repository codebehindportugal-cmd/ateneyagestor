<?php

namespace App\Services\PurchaseInvoices;

use Intervention\Image\ImageManager;
use thiagoalessio\TesseractOCR\TesseractOCR;

class ImageOcrExtractor
{
    public function extract(string $path): string
    {
        $binary = $this->binary(config('purchase_invoices.tesseract_binary'), 'tesseract');

        if (! $binary) {
            return '';
        }

        $ocrPath = $this->preprocess($path);

        try {
            return (new TesseractOCR($ocrPath))
                ->executable($binary)
                ->lang(config('purchase_invoices.tesseract_language', 'por+eng'))
                ->psm(6)
                ->run(120);
        } catch (\Throwable) {
            return '';
        } finally {
            if ($ocrPath !== $path && is_file($ocrPath)) {
                @unlink($ocrPath);
            }
        }
    }

    private function preprocess(string $path): string
    {
        $tmpPath = storage_path('app/private/purchase-invoice-ocr/'.uniqid('ocr_', true).'.png');

        try {
            if (! is_dir(dirname($tmpPath))) {
                mkdir(dirname($tmpPath), 0775, true);
            }

            $manager = extension_loaded('imagick') ? ImageManager::imagick() : ImageManager::gd();
            $image = $manager->read($path);
            $image->toPng()->save($tmpPath);

            return $tmpPath;
        } catch (\Throwable) {
            return $path;
        }
    }

    private function binary(?string $configured, string $command): ?string
    {
        if ($configured && is_file($configured)) {
            return $configured;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            foreach ([
                getenv('ProgramFiles').'\\Tesseract-OCR\\tesseract.exe',
                getenv('ProgramFiles(x86)').'\\Tesseract-OCR\\tesseract.exe',
            ] as $path) {
                if ($path && is_file($path)) {
                    return $path;
                }
            }

            return null;
        }

        return $configured ?: $command;
    }
}
