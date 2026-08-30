<?php

namespace App\Filament\Admin\Resources\AccountingDocumentResource\Pages;

use App\Filament\Admin\Resources\AccountingDocumentResource;
use App\Models\AccountingDocument;
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

    /**
     * Corre sozinho quando se carrega um PDF ou uma foto. Ao contrario do botao,
     * NAO por cima do que ja la esta: se corrigiste o fornecedor a mao e depois
     * juntas uma segunda foto, a tua correccao fica.
     */
    /** A primeira leitura enche o formulario; as seguintes so tapam buracos. */
    private bool $jaLeuFicheiro = false;

    public function autoReadUploadedInvoice(): void
    {
        $paths = $this->uploadedAbsolutePaths($this->data ?? []);

        if ($paths === []) {
            return;
        }

        $extractor = app(PaperInvoiceExtractor::class);
        $result = $this->extractDocuments($paths, $extractor);

        // Na primeira leitura escreve tudo. Se so preenchesse o que esta vazio,
        // campos com valor por omissao — a Data comeca em hoje, o Estado em
        // "pendente" — nunca seriam corrigidos, e a data da factura ficava a de
        // hoje. A partir da segunda ja respeita o que la esta, para nao apagar
        // correccoes tuas quando juntas outra foto.
        $this->fillFromExtraction($result, keepCurrentPurpose: true, onlyEmpty: $this->jaLeuFicheiro);
        $this->jaLeuFicheiro = true;
        $this->avisarFerramentasEmFalta($result['warnings'] ?? []);
    }

    /**
     * Sem o Poppler/zbar/tesseract instalados a extraccao devolve tudo vazio e
     * nao falha — o utilizador ficava a olhar para um formulario em branco sem
     * perceber porque. Isto poe a razao a vista em vez de a esconder nas Notas.
     */
    private function avisarFerramentasEmFalta(array $warnings): void
    {
        $criticos = array_values(array_filter(
            $warnings,
            fn (string $w) => str_contains($w, 'indisponivel'),
        ));

        if ($criticos === []) {
            return;
        }

        Notification::make()
            ->warning()
            ->title('Leitura automática indisponível no servidor')
            ->body(implode("\n", $criticos))
            ->persistent()
            ->send();
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
        $this->avisarFerramentasEmFalta($result['warnings'] ?? []);

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

    private function fillFromExtraction(
        array $result,
        bool $keepCurrentPurpose = false,
        bool $onlyEmpty = false,
    ): void {
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

        // O nome que ja usaste para este NIF vale mais do que o palpite do texto.
        $nomeConhecido = AccountingDocument::fornecedorPorNif($supplier['taxNumber'] ?? null);

        $novos = [
            'tipo' => $this->mapDocumentType($invoice['type'] ?? null),
            'estado' => $state['estado'] ?? 'pendente',
            // A Finalidade e' uma escolha tua, nao se le do PDF: nenhuma factura
            // diz se o gasoleo foi para as carrinhas ou para revender. Antes
            // enchia-se com o nome do fornecedor, o que so dava trabalho a
            // apagar — e agora nem seria uma opcao valida da lista.
            'title' => $state['title'] ?? null,
            'invoice_number' => $invoice['number'] ?: ($state['invoice_number'] ?? null),
            'fornecedor' => $nomeConhecido ?: ($supplier['name'] ?: ($state['fornecedor'] ?? null)),
            'supplier_nif' => $supplier['taxNumber'] ?: ($state['supplier_nif'] ?? null),
            'atcud' => $invoice['atcud'] ?: ($state['atcud'] ?? null),
            'date' => $this->parseExtractedDate($invoice['date'] ?? null),
            // O campo mostra euros mas a coluna guarda centimos: o
            // afterStateHydrated divide por 100 ao preencher. Entregar 553.75
            // fazia aparecer 5,54 €. E o valor que ja esta no formulario tambem
            // vem em euros, por isso leva a mesma conversao — sem isto, carregar
            // duas vezes no botao dividia o total por 100 de cada vez.
            'amount_cents' => $this->paraCentimos($invoice['total'] ?? 0, $state['amount_cents'] ?? 0),
            'iva_cents' => $this->paraCentimos($invoice['vatTotal'] ?? 0, $state['iva_cents'] ?? 0),
            'currency' => $invoice['currency'] ?? ($state['currency'] ?? 'EUR'),
            'category' => $state['category'] ?? 'fornecedores',
            'products' => $this->normalizeProducts($products),
            'notes' => implode("\n\n", array_filter($notes)),
        ];

        if ($onlyEmpty) {
            $novos = array_filter(
                $novos,
                fn (string $campo) => blank($state[$campo] ?? null),
                ARRAY_FILTER_USE_KEY,
            );
        }

        $this->form->fill(array_merge($state, $novos));
    }

    /** Euros (do QR ou do formulario) para centimos, que e' o que o fill() espera. */
    private function paraCentimos(mixed $extraido, mixed $atual): int
    {
        $euros = ((float) $extraido) > 0 ? (float) $extraido : (float) $atual;

        return (int) round($euros * 100);
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
