@extends('supplier-invoices._layout', ['title' => 'Faturas de fornecedores'])

@section('content')
    @php
        $statusBadges = [
            'uploaded'     => ['label' => 'Enviada',        'class' => 'bg-slate-100 text-slate-600 ring-slate-200'],
            'processing'   => ['label' => 'A processar',    'class' => 'bg-sky-50 text-sky-700 ring-sky-200'],
            'needs_review' => ['label' => 'Por rever',      'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
            'reviewed'     => ['label' => 'Revista',        'class' => 'bg-indigo-50 text-indigo-700 ring-indigo-200'],
            'confirmed'    => ['label' => 'Confirmada',     'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
            'failed'       => ['label' => 'Erro',           'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        ];
    @endphp

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-left text-[11px] uppercase tracking-wider text-slate-500">
                        <th class="px-4 py-2.5 font-semibold">Data</th>
                        <th class="px-4 py-2.5 font-semibold">Marca</th>
                        <th class="px-4 py-2.5 font-semibold">Fornecedor</th>
                        <th class="px-4 py-2.5 font-semibold">Finalidade</th>
                        <th class="px-4 py-2.5 font-semibold">Categoria</th>
                        <th class="px-4 py-2.5 font-semibold text-right">Total</th>
                        <th class="px-4 py-2.5 font-semibold">Estado</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $invoice)
                        @php $badge = $statusBadges[$invoice->status] ?? ['label' => ucfirst($invoice->status), 'class' => 'bg-slate-100 text-slate-600 ring-slate-200']; @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap tnum text-slate-600">{{ $invoice->invoice_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $invoice->brand?->full_name ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $invoice->supplier_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $invoice->purpose }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200">{{ $invoice->category_label }}</span>
                            </td>
                            <td class="px-4 py-3 text-right tnum font-semibold text-slate-900 whitespace-nowrap">{{ $invoice->total !== null ? number_format((float) $invoice->total, 2, ',', '.') . ' ' . $invoice->currency : '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a class="text-sm font-medium text-slate-600 hover:text-slate-900" href="{{ route('supplier-invoices.show', $invoice) }}">Ver</a>
                                <span class="mx-1.5 text-slate-300">·</span>
                                <a class="text-sm font-medium text-indigo-600 hover:text-indigo-800" href="{{ route('supplier-invoices.review', $invoice) }}">Rever</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-14 text-center">
                                <svg class="mx-auto mb-3 h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="font-medium text-slate-500">Ainda não há faturas enviadas.</p>
                                <a href="{{ route('supplier-invoices.create') }}" class="mt-2 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-800">Enviar a primeira fatura →</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $invoices->links() }}</div>
@endsection
