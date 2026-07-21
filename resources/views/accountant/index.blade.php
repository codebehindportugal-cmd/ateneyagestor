<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos para Contabilidade · {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', system-ui, sans-serif; }
        .tnum { font-variant-numeric: tabular-nums; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900 antialiased">

    {{-- Top bar --}}
    <header class="bg-slate-900 text-white no-print">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3.5">
                <img src="{{ asset('images/ateneya-logo.jpg') }}" alt="Ateneya" class="h-10 w-10 rounded-lg object-cover ring-1 ring-white/20">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-[0.18em] text-slate-400">Ateneya · Portal de Contabilidade</p>
                    <h1 class="text-lg font-semibold leading-tight">Documentos para Contabilidade</h1>
                </div>
            </div>
            <div class="sm:text-right">
                <p class="text-xl font-semibold tnum leading-tight">{{ number_format($grandTotal['amount'], 2, ',', '.') }} €</p>
                <p class="text-xs text-slate-400">{{ $grandTotal['count'] }} documento(s) · gerado a {{ now()->format('d/m/Y \à\s H:i') }}</p>
            </div>
        </div>
    </header>
    <div class="h-1 bg-gradient-to-r from-indigo-500 via-sky-500 to-emerald-500 no-print"></div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-12">

        @if(($supplierInvoices ?? collect())->isNotEmpty())
            <section>
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                    <h2 class="text-base font-semibold text-slate-800">Faturas de fornecedores confirmadas</h2>
                    <span class="ml-auto inline-flex items-center gap-1.5 text-sm text-slate-500 bg-white border border-slate-200 rounded-full px-3.5 py-1 shadow-sm whitespace-nowrap">
                        {{ $supplierGrandTotal['count'] }} fatura(s) ·
                        <span class="font-semibold text-slate-800 tnum">{{ number_format($supplierGrandTotal['amount'], 2, ',', '.') }} €</span>
                    </span>
                </div>

                <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-[11px] text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
                                    <th class="px-4 py-2.5 text-left font-semibold">Marca</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Fornecedor</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Nº Documento</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Finalidade</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Categoria</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Data</th>
                                    <th class="px-4 py-2.5 text-right font-semibold">IVA</th>
                                    <th class="px-4 py-2.5 text-right font-semibold">Total</th>
                                    <th class="px-4 py-2.5 text-center font-semibold no-print">Ficheiros</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($supplierInvoices as $invoice)
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="px-4 py-3 text-slate-700">{{ $invoice->brand?->full_name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-700">
                                            <span class="font-medium text-slate-800">{{ $invoice->supplier_name ?? '—' }}</span>
                                            @if($invoice->supplier_tax_number)
                                                <p class="text-xs text-slate-400 tnum">NIF {{ $invoice->supplier_tax_number }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-500 tnum text-xs whitespace-nowrap">{{ $invoice->invoice_number ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-700 min-w-48">
                                            <div class="font-medium">{{ $invoice->purpose }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200">
                                                {{ $invoice->category_label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap tnum">{{ $invoice->invoice_date?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right tnum text-slate-500 whitespace-nowrap">{{ number_format((float) $invoice->tax_total, 2, ',', '.') }} €</td>
                                        <td class="px-4 py-3 text-right tnum font-semibold text-slate-900 whitespace-nowrap">{{ number_format((float) $invoice->total, 2, ',', '.') }} €</td>
                                        <td class="px-4 py-3 text-center no-print whitespace-nowrap">
                                            <a href="{{ route('contabilista.supplier-invoices.download', ['token' => $token, 'supplierInvoice' => $invoice]) }}"
                                               class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                                Download
                                            </a>
                                            @foreach(($invoice->image_paths ?? []) as $index => $path)
                                                <a href="{{ route('contabilista.supplier-invoices.download', ['token' => $token, 'supplierInvoice' => $invoice, 'image' => $index]) }}"
                                                   class="ml-2 text-xs text-slate-500 hover:text-slate-700">Foto {{ $index + 1 }}</a>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        @forelse($brandGroups as $brandId => $brandData)
            @php
                $brand       = $brandData['brand'];
                $brandName   = $brand ? $brand->name : 'Geral';
                $brandColor  = $brand?->color ?? '#94a3b8';
                $brandParent = $brand?->parent?->name;
            @endphp

            <section>
                {{-- Brand header --}}
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $brandColor }}"></span>
                    <h2 class="text-base font-semibold text-slate-800">{{ $brandName }}</h2>
                    @if($brandParent)
                        <span class="text-xs text-slate-500 bg-slate-200/70 rounded-full px-2.5 py-0.5">{{ $brandParent }}</span>
                    @endif
                    <span class="ml-auto inline-flex items-center gap-1.5 text-sm text-slate-500 bg-white border border-slate-200 rounded-full px-3.5 py-1 shadow-sm whitespace-nowrap">
                        {{ $brandData['total']['count'] }} doc(s) ·
                        <span class="font-semibold text-slate-800 tnum">{{ number_format($brandData['total']['amount'], 2, ',', '.') }} €</span>
                    </span>
                </div>

                <div class="space-y-6">
                    @foreach($brandData['grouped'] as $year => $months)
                        <div>
                            {{-- Year sub-header --}}
                            <div class="flex items-center justify-between mb-2 px-1">
                                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest">{{ $year }}</h3>
                                <span class="text-xs text-slate-400">
                                    {{ $brandData['yearTotals'][$year]['count'] }} doc(s) ·
                                    <span class="font-semibold text-slate-600 tnum">{{ number_format($brandData['yearTotals'][$year]['amount'], 2, ',', '.') }} €</span>
                                </span>
                            </div>

                            <div class="space-y-4">
                                @foreach($months as $month => $docs)
                                    @php
                                        $monthTotal = $docs->sum('amount_cents') / 100;
                                        $monthCount = $docs->count();
                                    @endphp

                                    <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
                                        {{-- Month header --}}
                                        <div class="flex items-center justify-between px-5 py-3 bg-slate-50 border-b border-slate-200">
                                            <h4 class="font-semibold text-slate-700">
                                                {{ \App\Models\AccountingDocument::monthName($month) }}
                                            </h4>
                                            <span class="text-sm text-slate-500">
                                                {{ $monthCount }} doc(s) ·
                                                <span class="font-semibold text-slate-800 tnum">{{ number_format($monthTotal, 2, ',', '.') }} €</span>
                                            </span>
                                        </div>

                                        {{-- Documents table --}}
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead>
                                                    <tr class="text-[11px] text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                                        <th class="px-4 py-2.5 text-left font-semibold">Tipo</th>
                                                        <th class="px-4 py-2.5 text-left font-semibold">Nº Documento</th>
                                                        <th class="px-4 py-2.5 text-left font-semibold">Fornecedor</th>
                                                        <th class="px-4 py-2.5 text-left font-semibold">Finalidade</th>
                                                        <th class="px-4 py-2.5 text-left font-semibold">Data</th>
                                                        <th class="px-4 py-2.5 text-right font-semibold">Total s/ IVA</th>
                                                        <th class="px-4 py-2.5 text-right font-semibold">IVA</th>
                                                        <th class="px-4 py-2.5 text-right font-semibold">Total</th>
                                                        <th class="px-4 py-2.5 text-center font-semibold">Estado</th>
                                                        <th class="px-4 py-2.5 text-center font-semibold no-print">Ficheiro</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    @foreach($docs->sortByDesc('date') as $doc)
                                                        @php
                                                            $totalComIva  = $doc->amount;
                                                            $iva          = $doc->iva;
                                                            $totalSemIva  = $totalComIva - $iva;
                                                        @endphp
                                                        <tr class="hover:bg-slate-50/70 transition-colors">
                                                            <td class="px-4 py-3">
                                                                @php $tipos = \App\Models\AccountingDocument::tipos(); @endphp
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200">
                                                                    {{ $tipos[$doc->tipo ?? 'fatura'] ?? ucfirst($doc->tipo ?? 'fatura') }}
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-3 text-slate-500 tnum text-xs whitespace-nowrap">
                                                                {{ $doc->invoice_number ?? '—' }}
                                                                @if($doc->atcud)
                                                                    <p class="text-slate-300 mt-0.5">{{ $doc->atcud }}</p>
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-3 text-slate-700 text-sm">
                                                                <span class="font-medium text-slate-800">{{ $doc->fornecedor ?? $doc->title }}</span>
                                                                @if($doc->supplier_nif)
                                                                    <p class="text-xs text-slate-400 tnum">NIF {{ $doc->supplier_nif }}</p>
                                                                @endif
                                                            </td>
                                                            <td class="px-4 py-3 text-slate-700 text-sm min-w-48">
                                                                <div class="font-medium">{{ $doc->title }}</div>
                                                                <div class="mt-1 flex flex-wrap gap-1">
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200">
                                                                        {{ $doc->category_label }}
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap text-sm tnum">
                                                                {{ $doc->date->format('d/m/Y') }}
                                                            </td>
                                                            <td class="px-4 py-3 text-right tnum text-slate-700 whitespace-nowrap text-sm">
                                                                {{ number_format($totalSemIva, 2, ',', '.') }} €
                                                            </td>
                                                            <td class="px-4 py-3 text-right tnum text-slate-500 whitespace-nowrap text-sm">
                                                                {{ number_format($iva, 2, ',', '.') }} €
                                                            </td>
                                                            <td class="px-4 py-3 text-right tnum font-semibold text-slate-900 whitespace-nowrap">
                                                                {{ number_format($totalComIva, 2, ',', '.') }} €
                                                            </td>
                                                            <td class="px-4 py-3 text-center">
                                                                @php
                                                                    $estadoBadges = [
                                                                        'pendente' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                                                        'aprovado' => 'bg-sky-50 text-sky-700 ring-sky-200',
                                                                        'pago'     => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                                                    ];
                                                                    $estadoNames = \App\Models\AccountingDocument::estados();
                                                                    $estadoKey   = $doc->estado ?? 'pendente';
                                                                    $badge       = $estadoBadges[$estadoKey] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
                                                                @endphp
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium ring-1 ring-inset {{ $badge }}">
                                                                    {{ $estadoNames[$estadoKey] ?? ucfirst($estadoKey) }}
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-3 text-center no-print">
                                                                <a href="{{ route('contabilista.details', ['token' => $token, 'id' => $doc->id]) }}"
                                                                   class="inline-flex items-center gap-1 text-slate-600 hover:text-slate-900 text-xs font-medium">
                                                                    Detalhes
                                                                </a>
                                                                @if($doc->file_path)
                                                                    <a href="{{ route('contabilista.download', ['token' => $token, 'id' => $doc->id]) }}"
                                                                       class="ml-2 inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-xs font-medium"
                                                                       target="_blank">
                                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                                        </svg>
                                                                        Download
                                                                    </a>
                                                                @endif
                                                                @if(! empty($doc->image_paths))
                                                                    <div class="mt-1 flex flex-col items-center gap-1">
                                                                        @foreach(array_values($doc->image_paths) as $index => $imagePath)
                                                                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath) }}"
                                                                               class="text-xs text-slate-500 hover:text-slate-700"
                                                                               target="_blank">
                                                                                Imagem {{ $index + 1 }}
                                                                            </a>
                                                                        @endforeach
                                                                    </div>
                                                                @endif
                                                                @if(! $doc->file_path && empty($doc->image_paths))
                                                                    <span class="text-slate-300 text-xs">—</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                {{-- Month subtotal --}}
                                                <tfoot>
                                                    <tr class="bg-slate-50 border-t border-slate-200">
                                                        <td colspan="7" class="px-4 py-2.5 text-xs text-slate-500 font-medium">
                                                            Total {{ \App\Models\AccountingDocument::monthName($month) }}
                                                        </td>
                                                        <td class="px-4 py-2.5 text-right font-bold text-slate-900 tnum">
                                                            {{ number_format($monthTotal, 2, ',', '.') }} €
                                                        </td>
                                                        <td colspan="2" class="no-print"></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

        @empty
            <div class="bg-white rounded-xl ring-1 ring-slate-200 p-14 text-center shadow-sm">
                <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-slate-500 font-medium">Ainda não há documentos disponíveis.</p>
                <p class="text-slate-400 text-sm mt-1">Assim que forem carregados documentos, aparecem aqui automaticamente.</p>
            </div>
        @endforelse

        {{-- Footer --}}
        <footer class="border-t border-slate-200 pt-5 pb-6 text-center">
            <p class="text-xs text-slate-400">
                Este acesso é pessoal e intransmissível · {{ config('app.name') }}
            </p>
        </footer>
    </div>

</body>
</html>
