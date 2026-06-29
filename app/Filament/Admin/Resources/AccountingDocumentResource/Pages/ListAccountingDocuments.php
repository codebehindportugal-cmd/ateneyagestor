<?php

namespace App\Filament\Admin\Resources\AccountingDocumentResource\Pages;

use App\Filament\Admin\Resources\AccountingDocumentResource;
use App\Models\AccountingDocument;
use App\Models\Brand;
use App\Services\PaperInvoice\PaperInvoiceExtractor;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListAccountingDocuments extends ListRecords
{
    protected static string $resource = AccountingDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('scanPaperInvoice')
                ->label('Ler fatura PDF/imagem')
                ->icon('heroicon-o-camera')
                ->color('gray')
                ->form([
                    Forms\Components\Select::make('brand_id')
                        ->label('Marca / Empresa')
                        ->options(fn () => Brand::selectOptions())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\TextInput::make('title')
                        ->label('Para que foi / finalidade')
                        ->placeholder('Ex: fornecedor, material para empresa, software, stock...')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('category')
                        ->label('Categoria')
                        ->options(AccountingDocument::categories())
                        ->default('fornecedores')
                        ->required(),
                    Forms\Components\FileUpload::make('document')
                        ->label('PDF ou fotos da fatura')
                        ->disk('public')
                        ->directory('paper-invoices/tmp')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/bmp', 'image/tiff'])
                        ->multiple()
                        ->reorderable()
                        ->maxSize(20480)
                        ->storeFileNamesIn('document_name')
                        ->helperText('Podes tirar foto no telemóvel, selecionar imagem ou carregar PDF.')
                        ->required(),
                ])
                ->action(function (array $data, PaperInvoiceExtractor $extractor) {
                    $relativePaths = $this->uploadedPaths($data['document'] ?? []);
                    $originalNames = $this->uploadedNames($data['document_name'] ?? [], $relativePaths);
                    $result = $this->extractDocuments($relativePaths, $extractor);

                    $payload = [
                        'brand_id' => $data['brand_id'],
                        'title' => $data['title'],
                        'category' => $data['category'],
                        'original_name' => reset($originalNames) ?: basename(reset($relativePaths)),
                        'result' => $result,
                        'image_paths' => [],
                        'image_names' => [],
                    ];

                    foreach ($relativePaths as $index => $relativePath) {
                        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION) ?: 'jpg');
                        $originalName = $originalNames[$index] ?? basename($relativePath);

                        if ($extension === 'pdf' && empty($payload['file_path'])) {
                            $savedPath = 'accounting-documents/'.Str::uuid().'.pdf';
                            Storage::disk('public')->move($relativePath, $savedPath);

                            $payload['file_path'] = $savedPath;
                            $payload['file_name'] = $originalName;
                            continue;
                        }

                        $savedPath = 'accounting-document-images/'.Str::uuid().'.'.$extension;
                        Storage::disk('public')->move($relativePath, $savedPath);

                        $payload['image_paths'][] = $savedPath;
                        $payload['image_names'][] = $originalName;
                    }

                    $document = $this->createDocumentFromPayload($payload);

                    $notification = Notification::make()
                        ->title('Fatura criada para revisao')
                        ->body($this->extractionNotificationBody($result, count($document->products ?? [])));

                    ($result['needsManualReview'] ? $notification->warning() : $notification->success())->send();

                    return redirect(AccountingDocumentResource::getUrl('edit', ['record' => $document]));
                }),
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Todos'),
        ];

        $years = AccountingDocument::query()
            ->selectRaw('DISTINCT year')
            ->orderByDesc('year')
            ->pluck('year');

        foreach ($years as $year) {
            $total = AccountingDocument::where('year', $year)->sum('amount_cents') / 100;
            $tabs[(string) $year] = Tab::make((string) $year)
                ->badge(number_format($total, 0, ',', '.') . ' €')
                ->modifyQueryUsing(fn ($query) => $query->where('year', $year));
        }

        return $tabs;
    }

    private function extractionNotificationBody(array $result, int $productsCount = 0): string
    {
        $warnings = $result['warnings'] ?? [];
        $body = "O documento foi criado. Confirma os campos antes de enviar ao contabilista.\nProdutos encontrados: {$productsCount}.";

        if ($warnings !== []) {
            $body .= "\n\nAvisos:\n- ".implode("\n- ", array_slice($warnings, 0, 4));
        }

        return $body;
    }

    private function createDocumentFromPayload(array $payload): AccountingDocument
    {
        $result = $payload['result'] ?? [];
        $supplier = $result['supplier'] ?? [];
        $invoice = $result['invoice'] ?? [];
        $warnings = $result['warnings'] ?? [];
        $products = $this->normalizeProducts($result['products'] ?? []);

        $notes = [];
        if ($warnings !== []) {
            $notes[] = "Avisos OCR:\n- ".implode("\n- ", $warnings);
        }
        if (($result['rawText'] ?? '') !== '') {
            $notes[] = "Texto OCR:\n".mb_substr((string) $result['rawText'], 0, 4000);
        }

        return AccountingDocument::create([
            'tipo' => $this->mapDocumentType($invoice['type'] ?? null),
            'title' => $payload['title'],
            'invoice_number' => $invoice['number'] ?: null,
            'fornecedor' => $supplier['name'] ?: null,
            'supplier_nif' => $supplier['taxNumber'] ?: null,
            'atcud' => $invoice['atcud'] ?: null,
            'estado' => 'pendente',
            'amount_cents' => $this->toCents($invoice['total'] ?? 0),
            'iva_cents' => $this->toCents($invoice['vatTotal'] ?? 0),
            'currency' => $invoice['currency'] ?? 'EUR',
            'date' => $this->parseExtractedDate($invoice['date'] ?? null),
            'category' => $payload['category'] ?? 'fornecedores',
            'notes' => implode("\n\n", $notes),
            'products' => $products,
            'brand_id' => $payload['brand_id'],
            'file_path' => $payload['file_path'] ?? null,
            'file_name' => $payload['file_name'] ?? null,
            'image_paths' => $payload['image_paths'] ?? [],
            'image_names' => $payload['image_names'] ?? [],
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

    private function parseExtractedDate(?string $date): string
    {
        if ($date) {
            try {
                return Carbon::createFromFormat('d/m/Y', $date)->toDateString();
            } catch (\Throwable) {
                //
            }
        }

        return now()->toDateString();
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

    private function uploadedPaths(array|string $document): array
    {
        return collect(is_array($document) ? $document : [$document])
            ->flatten()
            ->filter()
            ->values()
            ->all();
    }

    private function uploadedNames(array|string|null $names, array $paths): array
    {
        $names = collect(is_array($names) ? $names : [$names])
            ->flatten()
            ->values();

        return collect($paths)
            ->map(fn (string $path, int $index) => $names[$index] ?? basename($path))
            ->all();
    }

    private function extractDocuments(array $relativePaths, PaperInvoiceExtractor $extractor): array
    {
        $results = collect($relativePaths)
            ->map(fn (string $path) => $extractor->extract(Storage::disk('public')->path($path)))
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

        $products = collect($results)
            ->flatMap(fn (array $result) => $result['products'] ?? [])
            ->unique(fn (array $product) => ($product['description'] ?? '').'|'.($product['lineTotal'] ?? ''))
            ->values()
            ->all();

        $warnings = collect($results)
            ->flatMap(fn (array $result) => $result['warnings'] ?? [])
            ->unique()
            ->values()
            ->all();

        $rawText = collect($results)
            ->map(fn (array $result) => $result['rawText'] ?? '')
            ->filter()
            ->implode("\n\n--- ficheiro seguinte ---\n\n");

        return array_merge($first, [
            'supplier' => $supplier,
            'invoice' => $invoice,
            'products' => $products,
            'warnings' => $warnings,
            'rawText' => $rawText,
            'needsManualReview' => true,
            'confidence' => min(1, max(array_column($results, 'confidence') ?: [0])),
        ]);
    }
}
