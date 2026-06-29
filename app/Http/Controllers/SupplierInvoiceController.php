<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmSupplierInvoiceRequest;
use App\Http\Requests\StoreSupplierInvoiceUploadRequest;
use App\Http\Requests\UpdateSupplierInvoiceReviewRequest;
use App\Jobs\ProcessSupplierInvoiceUpload;
use App\Models\Brand;
use App\Models\SupplierInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SupplierInvoiceController extends Controller
{
    public function index(): View
    {
        $invoices = SupplierInvoice::with('brand')
            ->latest('invoice_date')
            ->latest()
            ->paginate(25);

        return view('supplier-invoices.index', compact('invoices'));
    }

    public function create(): View
    {
        return view('supplier-invoices.upload', [
            'brands' => Brand::selectOptions(),
            'categories' => SupplierInvoice::categories(),
        ]);
    }

    public function store(StoreSupplierInvoiceUploadRequest $request): RedirectResponse
    {
        $disk = Storage::disk(config('purchase_invoices.storage_disk', 'local'));
        $basePath = config('purchase_invoices.storage_path', 'private/purchase-invoices');

        $documents = $request->file('documents', []);

        if ($documents === []) {
            $documents = [$request->file('file')];
        }

        $file = array_shift($documents);
        $path = $file->store($basePath, config('purchase_invoices.storage_disk', 'local'));

        $imagePaths = [];
        $imageNames = [];

        foreach ($documents as $document) {
            $imagePaths[] = $document->store($basePath.'/supporting', config('purchase_invoices.storage_disk', 'local'));
            $imageNames[] = $document->getClientOriginalName();
        }

        foreach ($request->file('images', []) as $image) {
            $imagePaths[] = $image->store($basePath.'/images', config('purchase_invoices.storage_disk', 'local'));
            $imageNames[] = $image->getClientOriginalName();
        }

        $invoice = SupplierInvoice::create([
            'user_id' => $request->user()?->id,
            'brand_id' => $request->integer('brand_id'),
            'purpose' => $request->string('purpose')->toString(),
            'category' => $request->string('category')->toString(),
            'original_file_path' => $path,
            'original_file_name' => $file->getClientOriginalName(),
            'image_paths' => $imagePaths,
            'image_names' => $imageNames,
            'mime_type' => $disk->mimeType($path) ?: $file->getMimeType(),
            'status' => 'uploaded',
        ]);

        ProcessSupplierInvoiceUpload::dispatch($invoice->id);

        return redirect()
            ->route('supplier-invoices.show', $invoice)
            ->with('status', 'Fatura enviada. A extracao OCR foi iniciada.');
    }

    public function show(SupplierInvoice $supplierInvoice): View
    {
        $supplierInvoice->load('brand', 'items');

        return view('supplier-invoices.show', ['invoice' => $supplierInvoice]);
    }

    public function review(SupplierInvoice $supplierInvoice): View
    {
        $supplierInvoice->load('brand', 'items');

        return view('supplier-invoices.review', [
            'invoice' => $supplierInvoice,
            'brands' => Brand::selectOptions(),
            'categories' => SupplierInvoice::categories(),
            'items' => $this->reviewItems($supplierInvoice),
        ]);
    }

    public function update(UpdateSupplierInvoiceReviewRequest $request, SupplierInvoice $supplierInvoice): RedirectResponse
    {
        $data = $request->validated();
        $items = $this->cleanItems($data['items'] ?? []);
        unset($data['items']);

        $supplierInvoice->update($data + [
            'status' => $supplierInvoice->status === 'confirmed' ? 'confirmed' : 'reviewed',
            'extracted_data' => array_merge($supplierInvoice->extracted_data ?? [], ['items' => $items]),
        ]);

        return redirect()
            ->route('supplier-invoices.review', $supplierInvoice)
            ->with('status', 'Revisao guardada.');
    }

    public function confirm(ConfirmSupplierInvoiceRequest $request, SupplierInvoice $supplierInvoice): RedirectResponse
    {
        $data = $request->validated();
        $items = $this->cleanItems($data['items'] ?? []);
        unset($data['items']);

        DB::transaction(function () use ($supplierInvoice, $data, $items): void {
            $supplierInvoice->update($data + ['status' => 'confirmed']);
            $supplierInvoice->items()->delete();

            foreach ($items as $index => $item) {
                $supplierInvoice->items()->create($item + ['line_order' => $index + 1]);
            }
        });

        return redirect()
            ->route('supplier-invoices.show', $supplierInvoice)
            ->with('status', 'Fatura confirmada e disponivel para o contabilista.');
    }

    public function download(SupplierInvoice $supplierInvoice, ?int $image = null)
    {
        $disk = Storage::disk(config('purchase_invoices.storage_disk', 'local'));
        $path = $image === null
            ? $supplierInvoice->original_file_path
            : ($supplierInvoice->image_paths[$image] ?? null);

        abort_unless($path && $disk->exists($path), 404);

        $name = $image === null
            ? ($supplierInvoice->original_file_name ?: basename($path))
            : (($supplierInvoice->image_names[$image] ?? null) ?: basename($path));

        return $disk->download($path, $name);
    }

    public function destroy(SupplierInvoice $supplierInvoice): RedirectResponse
    {
        $disk = Storage::disk(config('purchase_invoices.storage_disk', 'local'));

        foreach (array_filter([$supplierInvoice->original_file_path, ...($supplierInvoice->image_paths ?? [])]) as $path) {
            $disk->delete($path);
        }

        $supplierInvoice->delete();

        return redirect()
            ->route('supplier-invoices.index')
            ->with('status', 'Fatura apagada.');
    }

    private function reviewItems(SupplierInvoice $invoice): array
    {
        if ($invoice->items->isNotEmpty()) {
            return $invoice->items->map(fn ($item) => $item->only([
                'description',
                'quantity',
                'unit_price',
                'tax_rate',
                'tax_amount',
                'total',
            ]))->all();
        }

        return $invoice->extracted_data['items'] ?? [];
    }

    private function cleanItems(array $items): array
    {
        return collect($items)
            ->filter(fn (array $item) => filled($item['description'] ?? null))
            ->map(fn (array $item) => [
                'description' => $item['description'],
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $item['unit_price'] ?? null,
                'tax_rate' => $item['tax_rate'] ?? null,
                'tax_amount' => $item['tax_amount'] ?? null,
                'total' => $item['total'] ?? null,
            ])
            ->values()
            ->all();
    }
}
