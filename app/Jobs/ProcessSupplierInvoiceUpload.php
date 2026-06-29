<?php

namespace App\Jobs;

use App\Models\SupplierInvoice;
use App\Services\PurchaseInvoices\InvoiceExtractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessSupplierInvoiceUpload implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $invoiceId)
    {
    }

    public function handle(InvoiceExtractionService $extractor): void
    {
        $invoice = SupplierInvoice::findOrFail($this->invoiceId);
        $disk = Storage::disk(config('purchase_invoices.storage_disk', 'local'));

        $invoice->update(['status' => 'processing', 'error_message' => null]);

        try {
            $mainPath = $disk->path($invoice->original_file_path);
            $extraPaths = collect($invoice->image_paths ?? [])
                ->map(fn (string $path) => $disk->path($path))
                ->filter(fn (string $path) => is_file($path))
                ->values()
                ->all();

            $result = $extractor->extract($mainPath, (string) $invoice->mime_type, $extraPaths);

            $invoice->update([
                'supplier_name' => $result['supplier_name'] ?? null,
                'supplier_tax_number' => $result['supplier_tax_number'] ?? null,
                'invoice_number' => $result['invoice_number'] ?? null,
                'invoice_date' => $result['invoice_date'] ?? null,
                'due_date' => $result['due_date'] ?? null,
                'subtotal' => $result['subtotal'] ?? null,
                'tax_total' => $result['tax_total'] ?? null,
                'total' => $result['total'] ?? null,
                'currency' => $result['currency'] ?? 'EUR',
                'raw_extracted_text' => $result['raw_text'] ?? '',
                'extracted_data' => $result,
                'status' => 'extracted',
            ]);
        } catch (\Throwable $e) {
            Log::error('Supplier invoice extraction failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            $invoice->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
