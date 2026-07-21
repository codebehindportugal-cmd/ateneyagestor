@extends('supplier-invoices._layout', ['title' => 'Fatura de fornecedor'])

@section('content')
    @php
        $statusBadges = [
            'uploaded'     => ['label' => 'Enviada',     'class' => 'bg-slate-100 text-slate-600 ring-slate-200'],
            'processing'   => ['label' => 'A processar', 'class' => 'bg-sky-50 text-sky-700 ring-sky-200'],
            'needs_review' => ['label' => 'Por rever',   'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
            'reviewed'     => ['label' => 'Revista',     'class' => 'bg-indigo-50 text-indigo-700 ring-indigo-200'],
            'confirmed'    => ['label' => 'Confirmada',  'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            'failed'       => ['label' => 'Erro',        'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        ];
        $badge = $statusBadges[$invoice->status] ?? ['label' => ucfirst($invoice->status), 'class' => 'bg-slate-100 text-slate-600 ring-slate-200'];
    @endphp

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <div class="mb-6 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ $invoice->supplier_name ?? 'Fornecedor por rever' }}</h2>
                    <p class="mt-0.5 text-sm text-slate-500">{{ $invoice->purpose }} · {{ $invoice->category_label }}</p>
                </div>
                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $badge['class'] }}">{{ $badge['label'] }}</span>
            </div>

            <dl class="grid gap-x-6 gap-y-4 text-sm md:grid-cols-3">
                <div><dt class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Marca</dt><dd class="font-medium">{{ $invoice->brand?->full_name ?? '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">NIF</dt><dd class="font-medium tnum">{{ $invoice->supplier_tax_number ?? '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Nº fatura</dt><dd class="font-medium tnum">{{ $invoice->invoice_number ?? '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Data</dt><dd class="font-medium tnum">{{ $invoice->invoice_date?->format('d/m/Y') ?? '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Vencimento</dt><dd class="font-medium tnum">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-slate-400 mb-0.5">Total</dt><dd class="font-semibold text-slate-900 tnum">{{ $invoice->total !== null ? number_format((float) $invoice->total, 2, ',', '.') . ' ' . $invoice->currency : '—' }}</dd></div>
            </dl>

            @php
                $displayItems = $invoice->items->isNotEmpty()
                    ? $invoice->items
                    : collect($invoice->extracted_data['items'] ?? []);
                $showingExtractedItems = $invoice->items->isEmpty() && $displayItems->isNotEmpty();
            @endphp

            <div class="mt-8 mb-3 flex flex-wrap items-center justify-between gap-2">
                <h3 class="font-semibold text-slate-800">Produtos / itens</h3>
                @if($showingExtractedItems)
                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200">Extraídos por OCR — falta rever e confirmar</span>
                @endif
            </div>
            <div class="overflow-x-auto rounded-lg ring-1 ring-slate-200">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-left text-[11px] uppercase tracking-wider text-slate-500">
                            <th class="px-3 py-2.5 font-semibold">Descrição</th>
                            <th class="px-3 py-2.5 font-semibold text-right">Qtd.</th>
                            <th class="px-3 py-2.5 font-semibold text-right">Preço</th>
                            <th class="px-3 py-2.5 font-semibold text-right">IVA</th>
                            <th class="px-3 py-2.5 font-semibold text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($displayItems as $item)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-3 py-2.5">{{ is_array($item) ? ($item['description'] ?? '') : $item->description }}</td>
                                <td class="px-3 py-2.5 text-right tnum">{{ is_array($item) ? ($item['quantity'] ?? '') : $item->quantity }}</td>
                                <td class="px-3 py-2.5 text-right tnum">{{ is_array($item) ? ($item['unit_price'] ?? '') : $item->unit_price }}</td>
                                <td class="px-3 py-2.5 text-right tnum">{{ is_array($item) ? ($item['tax_rate'] ?? '') : $item->tax_rate }}</td>
                                <td class="px-3 py-2.5 text-right tnum font-medium">{{ is_array($item) ? ($item['total'] ?? '') : $item->total }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-8 text-center text-slate-500">
                                    @if(in_array($invoice->status, ['uploaded', 'processing'], true))
                                        OCR em processamento. Atualiza a página dentro de alguns segundos.
                                    @else
                                        Não foram encontrados produtos automaticamente. Podes adicioná-los manualmente em "Rever dados".
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="space-y-4">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-3 font-semibold text-slate-800">Ficheiros</h3>
                <a class="flex items-center gap-2 rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                   href="{{ route('supplier-invoices.download', $invoice) }}">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download principal
                </a>
                @foreach(($invoice->image_paths ?? []) as $index => $path)
                    <a class="mt-2 flex items-center gap-2 rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                       href="{{ route('supplier-invoices.image', [$invoice, $index]) }}">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Ficheiro {{ $index + 2 }}
                    </a>
                @endforeach
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <a class="block rounded-lg bg-slate-900 px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700"
                   href="{{ route('supplier-invoices.review', $invoice) }}">Rever dados</a>
                <form method="POST" action="{{ route('supplier-invoices.destroy', $invoice) }}" class="mt-2" onsubmit="return confirm('Apagar esta fatura?')">
                    @csrf
                    @method('DELETE')
                    <button class="w-full rounded-lg border border-rose-200 px-3.5 py-2.5 text-sm font-semibold text-rose-700 transition-colors hover:bg-rose-50">Apagar</button>
                </form>
            </div>
        </aside>
    </div>
@endsection
