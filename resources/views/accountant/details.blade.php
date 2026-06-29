<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do documento</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen text-gray-900">
    <div class="max-w-5xl mx-auto px-4 py-6 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold">Detalhes do documento</h1>
                <p class="text-sm text-gray-500">{{ $doc->title }}</p>
            </div>
            <a href="{{ route('contabilista.index', ['token' => $token]) }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700">Voltar</a>
        </div>

        <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold">Resumo</h2>
            </div>
            <dl class="grid gap-4 p-5 text-sm md:grid-cols-3">
                <div><dt class="text-gray-400">Marca</dt><dd class="font-medium">{{ $doc->brand?->full_name ?? 'Geral' }}</dd></div>
                <div><dt class="text-gray-400">Tipo</dt><dd class="font-medium">{{ \App\Models\AccountingDocument::tipos()[$doc->tipo ?? 'fatura'] ?? $doc->tipo }}</dd></div>
                <div><dt class="text-gray-400">Estado</dt><dd class="font-medium">{{ \App\Models\AccountingDocument::estados()[$doc->estado ?? 'pendente'] ?? $doc->estado }}</dd></div>
                <div><dt class="text-gray-400">Documento</dt><dd class="font-mono">{{ $doc->invoice_number ?? '-' }}</dd></div>
                <div><dt class="text-gray-400">Fornecedor</dt><dd class="font-medium">{{ $doc->fornecedor ?? '-' }}</dd></div>
                <div><dt class="text-gray-400">NIF</dt><dd class="font-mono">{{ $doc->supplier_nif ?? '-' }}</dd></div>
                <div><dt class="text-gray-400">Data</dt><dd>{{ $doc->date?->format('d/m/Y') ?? '-' }}</dd></div>
                <div><dt class="text-gray-400">Categoria</dt><dd>{{ $doc->category_label }}</dd></div>
                <div><dt class="text-gray-400">ATCUD</dt><dd class="font-mono">{{ $doc->atcud ?? '-' }}</dd></div>
                <div><dt class="text-gray-400">Total s/ IVA</dt><dd>{{ number_format($doc->amount - $doc->iva, 2, ',', '.') }} €</dd></div>
                <div><dt class="text-gray-400">IVA</dt><dd>{{ number_format($doc->iva, 2, ',', '.') }} €</dd></div>
                <div><dt class="text-gray-400">Total</dt><dd class="font-semibold">{{ number_format($doc->amount, 2, ',', '.') }} €</dd></div>
            </dl>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold">Produtos</h2>
                <span class="text-xs text-gray-400">{{ count($doc->products ?? []) }} item(s)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-400">
                        <tr>
                            <th class="px-4 py-2 text-left">Descrição</th>
                            <th class="px-4 py-2 text-right">Qtd.</th>
                            <th class="px-4 py-2 text-right">Preço un.</th>
                            <th class="px-4 py-2 text-right">IVA %</th>
                            <th class="px-4 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($doc->products ?? [] as $product)
                            <tr>
                                <td class="px-4 py-3">{{ $product['description'] ?? 'Produto' }}</td>
                                <td class="px-4 py-3 text-right">{{ $product['quantity'] ?? 1 }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((float) ($product['unitPrice'] ?? 0), 2, ',', '.') }} €</td>
                                <td class="px-4 py-3 text-right">{{ number_format((float) ($product['vatRate'] ?? 0), 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ number_format((float) ($product['lineTotal'] ?? 0), 2, ',', '.') }} €</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Sem produtos registados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="font-semibold mb-3">Ficheiros</h2>
            <div class="flex flex-wrap gap-2 text-sm">
                @if($doc->file_path)
                    <a href="{{ route('contabilista.download', ['token' => $token, 'id' => $doc->id]) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-indigo-600" target="_blank">Download PDF</a>
                @endif
                @foreach(($doc->image_paths ?? []) as $index => $imagePath)
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-indigo-600" target="_blank">Imagem {{ $index + 1 }}</a>
                @endforeach
            </div>
        </section>

        @if($doc->notes)
            <section class="bg-white rounded-xl border border-gray-200 p-5">
                <details>
                    <summary class="cursor-pointer font-semibold">Notas internas / OCR</summary>
                    <pre class="mt-3 whitespace-pre-wrap text-xs text-gray-500">{{ $doc->notes }}</pre>
                </details>
            </section>
        @endif
    </div>
</body>
</html>
