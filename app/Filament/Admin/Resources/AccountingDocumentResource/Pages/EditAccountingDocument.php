<?php

namespace App\Filament\Admin\Resources\AccountingDocumentResource\Pages;

use App\Filament\Admin\Resources\AccountingDocumentResource;
use App\Services\PaperInvoice\PaperInvoiceExtractor;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditAccountingDocument extends EditRecord
{
    protected static string $resource = AccountingDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reprocessOcr')
                ->label('Reprocessar OCR')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->action(fn (PaperInvoiceExtractor $extractor) => $this->reprocessOcr($extractor)),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function reprocessOcr(PaperInvoiceExtractor $extractor): void
    {
        $paths = collect([
            $this->record->file_path,
            ...array_values((array) $this->record->image_paths),
        ])
            ->filter(fn (?string $path) => $path && Storage::disk('public')->exists($path))
            ->map(fn (string $path) => Storage::disk('public')->path($path))
            ->values()
            ->all();

        if ($paths === []) {
            Notification::make()
                ->title('Este documento nao tem ficheiros para reprocessar')
                ->warning()
                ->send();

            return;
        }

        $result = $this->extractDocuments($paths, $extractor);
        $supplier = $result['supplier'] ?? [];
        $invoice = $result['invoice'] ?? [];
        $products = $this->normalizeProducts($result['products'] ?? []);
        $warnings = $result['warnings'] ?? [];

        $notes = [];
        if ($warnings !== []) {
            $notes[] = "Avisos OCR:\n- ".implode("\n- ", $warnings);
        }
        if (($result['rawText'] ?? '') !== '') {
            $notes[] = "Texto OCR:\n".mb_substr((string) $result['rawText'], 0, 4000);
        }

        $this->record->update([
            'tipo' => $this->mapDocumentType($invoice['type'] ?? null),
            'invoice_number' => $invoice['number'] ?: $this->record->invoice_number,
            'fornecedor' => $supplier['name'] ?: $this->record->fornecedor,
            'supplier_nif' => $supplier['taxNumber'] ?: $this->record->supplier_nif,
            'atcud' => $invoice['atcud'] ?: $this->record->atcud,
            'date' => $this->parseExtractedDate($invoice['date'] ?? null) ?: $this->record->date,
            'amount_cents' => ($invoice['total'] ?? 0) > 0 ? $this->toCents($invoice['total']) : $this->record->amount_cents,
            'iva_cents' => ($invoice['vatTotal'] ?? 0) > 0 ? $this->toCents($invoice['vatTotal']) : $this->record->iva_cents,
            'currency' => $invoice['currency'] ?? $this->record->currency,
            'products' => $products,
            'notes' => implode("\n\n", $notes),
        ]);

        $this->refreshFormData([
            'tipo',
            'invoice_number',
            'fornecedor',
            'supplier_nif',
            'atcud',
            'date',
            'amount_cents',
            'iva_cents',
            'currency',
            'products',
            'notes',
        ]);

        Notification::make()
            ->title('OCR reprocessado')
            ->body('Produtos encontrados: '.count($products).'. Confirma os campos antes de guardar.')
            ->success()
            ->send();
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

    private function parseExtractedDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function mapDocumentType(?string $type): string
    {
        return match ($type) {
            'NC' => 'nota_credito',
            'RC' => 'recibo',
            default => 'fatura',
        };
    }

    private function toCents(float|int|string|null $value): int
    {
        return (int) round((float) ($value ?? 0) * 100);
    }
}
