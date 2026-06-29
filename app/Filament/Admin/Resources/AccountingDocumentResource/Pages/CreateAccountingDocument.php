<?php

namespace App\Filament\Admin\Resources\AccountingDocumentResource\Pages;

use App\Filament\Admin\Resources\AccountingDocumentResource;
use App\Services\PaperInvoice\PaperInvoiceExtractor;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateAccountingDocument extends CreateRecord
{
    protected static string $resource = AccountingDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('readUploadedInvoice')
                ->label('Ler ficheiros carregados')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->action(fn (PaperInvoiceExtractor $extractor) => $this->readUploadedInvoice($extractor)),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        $payload = session()->pull('paper_invoice_extract');
        if (! is_array($payload) || ! isset($payload['result'])) {
            return;
        }

        $result = $payload['result'];
        $supplier = $result['supplier'] ?? [];
        $invoice = $result['invoice'] ?? [];
        $warnings = $result['warnings'] ?? [];
        $products = $result['products'] ?? [];
        $originalName = $payload['original_name'] ?? $payload['file_name'] ?? null;
        $fallbackTitle = $originalName
            ? 'Fatura em papel - '.pathinfo((string) $originalName, PATHINFO_FILENAME)
            : 'Fatura em papel';

        $notes = [];
        if ($warnings !== []) {
            $notes[] = "Avisos:\n- ".implode("\n- ", $warnings);
        }
        if (($result['rawText'] ?? '') !== '') {
            $notes[] = "Texto OCR:\n".mb_substr((string) $result['rawText'], 0, 4000);
        }

        $this->form->fill(array_merge($this->form->getState(), [
            'tipo' => $this->mapDocumentType($invoice['type'] ?? null),
            'estado' => 'pendente',
            'brand_id' => $payload['brand_id'] ?? null,
            'title' => $payload['title'] ?? ($supplier['name'] ?: ($invoice['number'] ? 'Fatura '.$invoice['number'] : $fallbackTitle)),
            'invoice_number' => $invoice['number'] ?? null,
            'fornecedor' => $supplier['name'] ?? null,
            'supplier_nif' => $supplier['taxNumber'] ?? null,
            'atcud' => $invoice['atcud'] ?? null,
            'date' => $this->parseExtractedDate($invoice['date'] ?? null),
            'amount_cents' => isset($invoice['total']) ? (float) $invoice['total'] : 0,
            'iva_cents' => isset($invoice['vatTotal']) ? (float) $invoice['vatTotal'] : 0,
            'currency' => $invoice['currency'] ?? 'EUR',
            'category' => $payload['category'] ?? 'fornecedores',
            'products' => $this->normalizeProducts($products),
            'file_path' => $payload['file_path'] ?? null,
            'file_name' => $payload['file_name'] ?? null,
            'image_paths' => $payload['image_paths'] ?? [],
            'image_names' => $payload['image_names'] ?? [],
            'notes' => implode("\n\n", $notes),
        ]));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function readUploadedInvoice(PaperInvoiceExtractor $extractor): void
    {
        $state = $this->data ?? [];
        $paths = $this->uploadedAbsolutePaths($state);

        if ($paths === []) {
            Notification::make()
                ->title('Carrega primeiro o PDF ou as fotos da fatura')
                ->warning()
                ->send();

            return;
        }

        $result = $this->extractDocuments($paths, $extractor);
        $this->fillFromExtraction($result, keepCurrentPurpose: true);

        Notification::make()
            ->title('Fatura lida')
            ->body(sprintf(
                'Campos preenchidos. Produtos encontrados: %d. Confirma antes de guardar.',
                count($result['products'] ?? []),
            ))
            ->success()
            ->send();
    }

    private function parseExtractedDate(?string $date): ?string
    {
        if (! $date) {
            return now()->toDateString();
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $date)->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    private function productsSummary(array $products): string
    {
        return collect($products)
            ->map(fn (array $product) => sprintf(
                '- %s | qtd %s | unit %.2f | IVA %s%% | total %.2f',
                $product['description'] ?? '',
                $product['quantity'] ?? 1,
                (float) ($product['unitPrice'] ?? 0),
                $product['vatRate'] ?? 0,
                (float) ($product['lineTotal'] ?? 0),
            ))
            ->implode("\n");
    }

    private function normalizeProducts(array $products): array
    {
        return collect($products)
            ->map(fn (array $product) => [
                'description' => $product['description'] ?? '',
                'quantity' => (float) ($product['quantity'] ?? 1),
                'unitPrice' => (float) ($product['unitPrice'] ?? 0),
                'vatRate' => (float) ($product['vatRate'] ?? 0),
                'lineTotal' => (float) ($product['lineTotal'] ?? 0),
                'confidence' => round((float) ($product['confidence'] ?? 0), 2),
            ])
            ->filter(fn (array $product) => trim((string) $product['description']) !== '')
            ->values()
            ->all();
    }

    private function mapDocumentType(?string $type): string
    {
        return match ($type) {
            'NC' => 'nota_credito',
            'RC' => 'recibo',
            default => 'fatura',
        };
    }

    private function fillFromExtraction(array $result, bool $keepCurrentPurpose = false): void
    {
        $state = $this->data ?? $this->form->getState();
        $supplier = $result['supplier'] ?? [];
        $invoice = $result['invoice'] ?? [];
        $warnings = $result['warnings'] ?? [];
        $products = $result['products'] ?? [];

        $notes = [];
        if (filled($state['notes'] ?? null)) {
            $notes[] = trim((string) $state['notes']);
        }
        if ($warnings !== []) {
            $notes[] = "Avisos OCR:\n- ".implode("\n- ", $warnings);
        }
        if (($result['rawText'] ?? '') !== '') {
            $notes[] = "Texto OCR:\n".mb_substr((string) $result['rawText'], 0, 4000);
        }

        $this->form->fill(array_merge($state, [
            'tipo' => $this->mapDocumentType($invoice['type'] ?? null),
            'estado' => $state['estado'] ?? 'pendente',
            'title' => $keepCurrentPurpose && filled($state['title'] ?? null)
                ? $state['title']
                : ($state['title'] ?? ($supplier['name'] ?: ($invoice['number'] ? 'Fatura '.$invoice['number'] : 'Fatura'))),
            'invoice_number' => $invoice['number'] ?: ($state['invoice_number'] ?? null),
            'fornecedor' => $supplier['name'] ?: ($state['fornecedor'] ?? null),
            'supplier_nif' => $supplier['taxNumber'] ?: ($state['supplier_nif'] ?? null),
            'atcud' => $invoice['atcud'] ?: ($state['atcud'] ?? null),
            'date' => $this->parseExtractedDate($invoice['date'] ?? null),
            'amount_cents' => ($invoice['total'] ?? 0) > 0 ? (float) $invoice['total'] : ($state['amount_cents'] ?? 0),
            'iva_cents' => ($invoice['vatTotal'] ?? 0) > 0 ? (float) $invoice['vatTotal'] : ($state['iva_cents'] ?? 0),
            'currency' => $invoice['currency'] ?? ($state['currency'] ?? 'EUR'),
            'category' => $state['category'] ?? 'fornecedores',
            'products' => $this->normalizeProducts($products),
            'notes' => implode("\n\n", array_filter($notes)),
        ]));
    }

    private function uploadedAbsolutePaths(array $state): array
    {
        return collect([
            $state['file_path'] ?? null,
            ...array_values((array) ($state['image_paths'] ?? [])),
        ])
            ->flatten()
            ->map(fn ($file) => $this->absolutePathForUploadedFile($file))
            ->filter()
            ->values()
            ->all();
    }

    private function absolutePathForUploadedFile(mixed $file): ?string
    {
        if (is_string($file) && Storage::disk('public')->exists($file)) {
            return Storage::disk('public')->path($file);
        }

        if (is_object($file) && method_exists($file, 'getRealPath')) {
            $path = $file->getRealPath();
            return is_string($path) && is_file($path) ? $path : null;
        }

        return null;
    }

    private function extractDocuments(array $absolutePaths, PaperInvoiceExtractor $extractor): array
    {
        $results = collect($absolutePaths)
            ->map(fn (string $path) => $extractor->extract($path))
            ->all();

        $first = $results[0] ?? [];
        $supplier = $first['supplier'] ?? [];
        $invoice = $first['invoice'] ?? [];

        foreach ($results as $result) {
            foreach (($result['supplier'] ?? []) as $key => $value) {
                if (in_array($supplier[$key] ?? null, [null, '', 0, 0.0], true) && filled($value)) {
                    $supplier[$key] = $value;
                }
            }

            foreach (($result['invoice'] ?? []) as $key => $value) {
                if (in_array($invoice[$key] ?? null, [null, '', 0, 0.0], true) && filled($value)) {
                    $invoice[$key] = $value;
                }
            }
        }

        return array_merge($first, [
            'supplier' => $supplier,
            'invoice' => $invoice,
            'products' => collect($results)
                ->flatMap(fn (array $result) => $result['products'] ?? [])
                ->unique(fn (array $product) => ($product['description'] ?? '').'|'.($product['lineTotal'] ?? ''))
                ->values()
                ->all(),
            'warnings' => collect($results)->flatMap(fn (array $result) => $result['warnings'] ?? [])->unique()->values()->all(),
            'rawText' => collect($results)->map(fn (array $result) => $result['rawText'] ?? '')->filter()->implode("\n\n--- ficheiro seguinte ---\n\n"),
        ]);
    }
}
