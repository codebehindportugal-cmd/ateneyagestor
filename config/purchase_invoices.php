<?php

return [
    'max_upload_size' => env('PURCHASE_INVOICE_MAX_UPLOAD_SIZE', 10240),
    'storage_disk' => env('PURCHASE_INVOICE_STORAGE_DISK', 'local'),
    'storage_path' => env('PURCHASE_INVOICE_STORAGE_PATH', 'private/purchase-invoices'),
    'tesseract_language' => env('PURCHASE_INVOICE_TESSERACT_LANGUAGE', 'por+eng'),
    'min_pdf_text_length' => env('PURCHASE_INVOICE_MIN_PDF_TEXT_LENGTH', 100),
    'ocrmypdf_binary' => env('OCRMYPDF_BINARY', 'ocrmypdf'),
    'pdftotext_binary' => env('PDFTOTEXT_BINARY', 'pdftotext'),
    'pdftoppm_binary' => env('PDFTOPPM_BINARY', 'pdftoppm'),
    'tesseract_binary' => env('TESSERACT_BINARY', 'tesseract'),
];
