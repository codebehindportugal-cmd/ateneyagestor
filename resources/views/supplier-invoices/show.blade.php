@extends('supplier-invoices._layout', ['title' => 'Fatura de fornecedor'])

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-lg border border-zinc-200 bg-white p-6 lg:col-span-2">
            <div class="mb-5 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">{{ $invoice->supplier_name ?? 'Fornecedor por rever' }}</h2>
                    <p class="text-sm text-zinc-500">{{ $invoice->purpose }} · {{ $invoice->category_label }}</p>
                </div>
                <span class="rounded bg-zinc-100 px-2 py-1 text-xs">{{ $invoice->status }}</span>
            </div>

            <dl class="grid gap-4 text-sm md:grid-cols-3">
                <div><dt class="text-zinc-500">Marca</dt><dd class="font-medium">{{ $invoice->brand?->full_name ?? '-' }}</dd></div>
                <div><dt class="text-zinc-500">NIF</dt><dd class="font-medium">{{ $invoice->supplier_tax_number ?? '-' }}</dd></div>
                <div><dt class="text-zinc-500">N. fatura</dt><dd class="font-medium">{{ $invoice->invoice_number ?? '-' }}</dd></div>
                <div><dt class="text-zinc-500">Data</dt><dd class="font-medium">{{ $invoice->invoice_date?->format('d/m/Y') ?? '-' }}</dd></div>
                <div><dt class="text-zinc-500">Vencimento</dt><dd class="font-medium">{{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</dd></div>
                <div><dt class="text-zinc-500">Total</dt><dd class="font-medium">{{ $invoice->total !== null ? number_format((float) $invoice->total, 2, ',', '.') . ' ' . $invoice->currency : '-' }}</dd></div>
            </dl>

            @php
                $displayItems = $invoice->items->isNotEmpty()
                    ? $invoice->items
                    : collect($invoice->extracted_data['items'] ?? []);
                $showingExtractedItems = $invoice->items->isEmpty() && $displayItems->isNotEmpty();
            @endphp

            <div class="mt-8 mb-3 flex flex-wrap items-center justify-between gap-2">
                <h3 class="font-semibold">Produtos / items</h3>
                @if($showingExtractedItems)
                    <span class="rounded bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700">Extraidos por OCR, falta rever e confirmar</span>
                @endif
            </div>
            <div class="overflow-x-auto rounded-md border border-zinc-200">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500">
                        <tr>
                            <th class="px-3 py-2">Descricao</th>
                            <th class="px-3 py-2 text-right">Qtd.</th>
                            <th class="px-3 py-2 text-right">Preco</th>
                            <th class="px-3 py-2 text-right">IVA</th>
                            <th class="px-3 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse($displayItems as $item)
                            <tr>
                                <td class="px-3 py-2">{{ is_array($item) ? ($item['description'] ?? '') : $item->description }}</td>
                                <td class="px-3 py-2 text-right">{{ is_array($item) ? ($item['quantity'] ?? '') : $item->quantity }}</td>
                                <td class="px-3 py-2 text-right">{{ is_array($item) ? ($item['unit_price'] ?? '') : $item->unit_price }}</td>
                                <td class="px-3 py-2 text-right">{{ is_array($item) ? ($item['tax_rate'] ?? '') : $item->tax_rate }}</td>
                                <td class="px-3 py-2 text-right">{{ is_array($item) ? ($item['total'] ?? '') : $item->total }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-zinc-500">
                                    @if(in_array($invoice->status, ['uploaded', 'processing'], true))
                                        OCR em processamento. Atualiza a pagina dentro de alguns segundos.
                                    @else
                                        Nao foram encontrados produtos automaticamente. Podes adiciona-los manualmente em Rever dados.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="space-y-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-5">
                <h3 class="mb-3 font-semibold">Ficheiros</h3>
                <a class="block rounded-md border border-zinc-200 px-3 py-2 text-sm" href="{{ route('supplier-invoices.download', $invoice) }}">Download principal</a>
                @foreach(($invoice->image_paths ?? []) as $index => $path)
                    <a class="mt-2 block rounded-md border border-zinc-200 px-3 py-2 text-sm" href="{{ route('supplier-invoices.image', [$invoice, $index]) }}">Ficheiro {{ $index + 2 }}</a>
                @endforeach
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5">
                <a class="block rounded-md bg-zinc-900 px-3 py-2 text-center text-sm font-semibold text-white" href="{{ route('supplier-invoices.review', $invoice) }}">Rever dados</a>
                <form method="POST" action="{{ route('supplier-invoices.destroy', $invoice) }}" class="mt-2" onsubmit="return confirm('Apagar esta fatura?')">
                    @csrf
                    @method('DELETE')
                    <button class="w-full rounded-md border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700">Apagar</button>
                </form>
            </div>
        </aside>
    </div>
@endsection
