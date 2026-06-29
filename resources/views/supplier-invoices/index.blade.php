@extends('supplier-invoices._layout', ['title' => 'Faturas de fornecedores'])

@section('content')
    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Data</th>
                    <th class="px-4 py-3">Marca</th>
                    <th class="px-4 py-3">Fornecedor</th>
                    <th class="px-4 py-3">Finalidade</th>
                    <th class="px-4 py-3">Categoria</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse($invoices as $invoice)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $invoice->invoice_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $invoice->brand?->full_name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $invoice->supplier_name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $invoice->purpose }}</td>
                        <td class="px-4 py-3">{{ $invoice->category_label }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ $invoice->total !== null ? number_format((float) $invoice->total, 2, ',', '.') . ' ' . $invoice->currency : '-' }}</td>
                        <td class="px-4 py-3"><span class="rounded bg-zinc-100 px-2 py-1 text-xs">{{ $invoice->status }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <a class="font-medium text-zinc-900" href="{{ route('supplier-invoices.show', $invoice) }}">Ver</a>
                            <span class="mx-1 text-zinc-300">/</span>
                            <a class="font-medium text-zinc-900" href="{{ route('supplier-invoices.review', $invoice) }}">Rever</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-zinc-500">Ainda nao ha faturas enviadas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $invoices->links() }}</div>
@endsection
