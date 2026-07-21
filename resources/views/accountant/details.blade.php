<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do documento · {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', system-ui, sans-serif; }
        .tnum { font-variant-numeric: tabular-nums; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900 antialiased">

    {{-- Top bar --}}
    <header class="bg-slate-900 text-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3.5 min-w-0">
                <img src="{{ asset('images/ateneya-logo.jpg') }}" alt="Ateneya" class="h-10 w-10 rounded-lg object-cover ring-1 ring-white/20 flex-shrink-0">
                <div class="min-w-0">
                    <p class="text-[11px] font-medium uppercase tracking-[0.18em] text-slate-400">Ateneya · Portal de Contabilidade</p>
                    <h1 class="text-lg font-semibold leading-tight truncate">{{ $doc->title }}</h1>
                </div>
            </div>
            <a href="{{ route('contabilista.index', ['token' => $token]) }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 hover:bg-white/20 px-3.5 py-2 text-sm font-medium text-white transition-colors flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Voltar
            </a>
        </div>
    </header>
    <div class="h-1 bg-gradient-to-r from-indigo-500 via-sky-500 to-emerald-500"></div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 space-y-6">

        <section class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60">
                <h2 class="font-semibold text-slate-800">Resumo</h2>
            </div>
            <dl class="grid gap-x-6 gap-y-4 p-5 text-sm md:grid-cols-3">
                <div><dt class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Marca</dt><dd class="font-medium">{{ $doc->brand?->full_name ?? 'Geral' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Tipo</dt><dd class="font-medium">{{ \App\Models\AccountingDocument::tipos()[$doc->tipo ?? 'fatura'] ?? $doc->tipo }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Estado</dt><dd class="font-medium">{{ \App\Models\AccountingDocument::estados()[$doc->estado ?? 'pendente'] ?? $doc->estado }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Documento</dt><dd class="tnum">{{ $doc->invoice_number ?? '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Fornecedor</dt><dd class="font-medium">{{ $doc->fornecedor ?? '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">NIF</dt><dd class="tnum">{{ $doc->supplier_nif ?? '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Data</dt><dd class="tnum">{{ $doc->date?->format('d/m/Y') ?? '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Categoria</dt><dd>{{ $doc->category_label }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">ATCUD</dt><dd class="tnum">{{ $doc->atcud ?? '—' }}</dd></div>
            </dl>
            <div class="grid gap-px bg-slate-200 border-t border-slate-200 sm:grid-cols-3 text-sm">
                <div class="bg-slate-50 px-5 py-3">
                    <p class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Total s/ IVA</p>
                    <p class="tnum font-medium text-slate-700">{{ number_format($doc->amount - $doc->iva, 2, ',', '.') }} €</p>
                </div>
                <div class="bg-slate-50 px-5 py-3">
                    <p class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">IVA</p>
                    <p class="tnum font-medium text-slate-700">{{ number_format($doc->iva, 2, ',', '.') }} €</p>
                </div>
                <div class="bg-slate-50 px-5 py-3">
                    <p class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Total</p>
                    <p class="tnum font-semibold text-slate-900">{{ number_format($doc->amount, 2, ',', '.') }} €</p>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Produtos</h2>
                <span class="text-xs text-slate-400">{{ count($doc->products ?? []) }} item(s)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[11px] uppercase tracking-wider text-slate-500 bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-2.5 text-left font-semibold">Descrição</th>
                            <th class="px-4 py-2.5 text-right font-semibold">Qtd.</th>
                            <th class="px-4 py-2.5 text-right font-semibold">Preço un.</th>
                            <th class="px-4 py-2.5 text-right font-semibold">IVA %</th>
                            <th class="px-4 py-2.5 text-right font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($doc->products ?? [] as $product)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-4 py-3">{{ $product['description'] ?? 'Produto' }}</td>
                                <td class="px-4 py-3 text-right tnum">{{ $product['quantity'] ?? 1 }}</td>
                                <td class="px-4 py-3 text-right tnum">{{ number_format((float) ($product['unitPrice'] ?? 0), 2, ',', '.') }} €</td>
                                <td class="px-4 py-3 text-right tnum">{{ number_format((float) ($product['vatRate'] ?? 0), 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right tnum font-medium">{{ number_format((float) ($product['lineTotal'] ?? 0), 2, ',', '.') }} €</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Sem produtos registados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200 p-5">
            <h2 class="font-semibold text-slate-800 mb-3">Ficheiros</h2>
            <div class="flex flex-wrap gap-2 text-sm">
                @if($doc->file_path)
                    <a href="{{ route('contabilista.download', ['token' => $token, 'id' => $doc->id]) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 px-3.5 py-2 font-medium text-white transition-colors" target="_blank">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download PDF
                    </a>
                @endif
                @foreach(($doc->image_paths ?? []) as $index => $imagePath)
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 px-3.5 py-2 font-medium text-slate-700 transition-colors" target="_blank">
                        Imagem {{ $index + 1 }}
                    </a>
                @endforeach
                @if(! $doc->file_path && empty($doc->image_paths))
                    <p class="text-slate-400">Sem ficheiros associados.</p>
                @endif
            </div>
        </section>

        @if($doc->notes)
            <section class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200 p-5">
                <details>
                    <summary class="cursor-pointer font-semibold text-slate-800 select-none">Notas internas / OCR</summary>
                    <pre class="mt-3 whitespace-pre-wrap text-xs text-slate-500 bg-slate-50 rounded-lg p-4 ring-1 ring-slate-100">{{ $doc->notes }}</pre>
                </details>
            </section>
        @endif

        <footer class="border-t border-slate-200 pt-5 pb-6 text-center">
            <p class="text-xs text-slate-400">
                Este acesso é pessoal e intransmissível · {{ config('app.name') }}
            </p>
        </footer>
    </div>
</body>
</html>
