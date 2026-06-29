@extends('supplier-invoices._layout', ['title' => 'Rever fatura'])

@section('content')
    <form method="POST" action="{{ route('supplier-invoices.update', $invoice) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-lg border border-zinc-200 bg-white p-6">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">Dados principais</h2>
                    <p class="text-sm text-zinc-500">Estado: {{ $invoice->status }} @if($invoice->error_message) · {{ $invoice->error_message }} @endif</p>
                </div>
                <a class="rounded-md border border-zinc-300 px-3 py-2 text-sm" href="{{ route('supplier-invoices.download', $invoice) }}">Abrir ficheiro</a>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <label class="block"><span class="text-sm font-medium">Marca</span><select name="brand_id" class="mt-1 w-full rounded-md border-zinc-300">@foreach($brands as $id => $name)<option value="{{ $id }}" @selected(old('brand_id', $invoice->brand_id) == $id)>{{ $name }}</option>@endforeach</select></label>
                <label class="block md:col-span-2"><span class="text-sm font-medium">Para que foi</span><input name="purpose" value="{{ old('purpose', $invoice->purpose) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
                <label class="block"><span class="text-sm font-medium">Categoria</span><select name="category" class="mt-1 w-full rounded-md border-zinc-300">@foreach($categories as $key => $label)<option value="{{ $key }}" @selected(old('category', $invoice->category) === $key)>{{ $label }}</option>@endforeach</select></label>
                <label class="block"><span class="text-sm font-medium">Fornecedor</span><input name="supplier_name" value="{{ old('supplier_name', $invoice->supplier_name) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
                <label class="block"><span class="text-sm font-medium">NIF</span><input name="supplier_tax_number" value="{{ old('supplier_tax_number', $invoice->supplier_tax_number) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
                <label class="block"><span class="text-sm font-medium">N. fatura</span><input name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
                <label class="block"><span class="text-sm font-medium">Moeda</span><input name="currency" maxlength="3" value="{{ old('currency', $invoice->currency) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
                <label class="block"><span class="text-sm font-medium">Data</span><input type="date" name="invoice_date" value="{{ old('invoice_date', $invoice->invoice_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
                <label class="block"><span class="text-sm font-medium">Vencimento</span><input type="date" name="due_date" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
                <label class="block"><span class="text-sm font-medium">Subtotal</span><input type="number" step="0.01" name="subtotal" value="{{ old('subtotal', $invoice->subtotal) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
                <label class="block"><span class="text-sm font-medium">IVA</span><input type="number" step="0.01" name="tax_total" value="{{ old('tax_total', $invoice->tax_total) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
                <label class="block"><span class="text-sm font-medium">Total</span><input type="number" step="0.01" name="total" value="{{ old('total', $invoice->total) }}" class="mt-1 w-full rounded-md border-zinc-300"></label>
            </div>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Items</h2>
                <button type="button" onclick="addItem()" class="rounded-md border border-zinc-300 px-3 py-2 text-sm">Adicionar item</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="items-table">
                    <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500">
                        <tr><th class="px-2 py-2">Descricao</th><th class="px-2 py-2">Qtd.</th><th class="px-2 py-2">Preco</th><th class="px-2 py-2">IVA %</th><th class="px-2 py-2">Valor IVA</th><th class="px-2 py-2">Total</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach(($items ?: [[]]) as $i => $item)
                            <tr class="border-b border-zinc-100">
                                <td class="px-2 py-2"><input name="items[{{ $i }}][description]" value="{{ $item['description'] ?? '' }}" class="w-72 rounded-md border-zinc-300"></td>
                                <td class="px-2 py-2"><input type="number" step="0.001" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" class="w-24 rounded-md border-zinc-300"></td>
                                <td class="px-2 py-2"><input type="number" step="0.0001" name="items[{{ $i }}][unit_price]" value="{{ $item['unit_price'] ?? '' }}" class="w-28 rounded-md border-zinc-300"></td>
                                <td class="px-2 py-2"><input type="number" step="0.01" name="items[{{ $i }}][tax_rate]" value="{{ $item['tax_rate'] ?? '' }}" class="w-24 rounded-md border-zinc-300"></td>
                                <td class="px-2 py-2"><input type="number" step="0.01" name="items[{{ $i }}][tax_amount]" value="{{ $item['tax_amount'] ?? '' }}" class="w-28 rounded-md border-zinc-300"></td>
                                <td class="px-2 py-2"><input type="number" step="0.01" name="items[{{ $i }}][total]" value="{{ $item['total'] ?? '' }}" class="w-28 rounded-md border-zinc-300"></td>
                                <td class="px-2 py-2"><button type="button" onclick="this.closest('tr').remove()" class="text-rose-700">Remover</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-6">
            <details>
                <summary class="cursor-pointer font-semibold">Texto extraido</summary>
                <textarea readonly class="mt-3 h-64 w-full rounded-md border-zinc-300 font-mono text-xs">{{ $invoice->raw_extracted_text }}</textarea>
            </details>
        </section>

        <div class="flex flex-wrap gap-3">
            <button class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold">Guardar revisao</button>
            <button formaction="{{ route('supplier-invoices.confirm', $invoice) }}" formmethod="POST" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white">Confirmar importacao</button>
        </div>
    </form>

    <script>
        let itemIndex = {{ max(count($items), 1) }};
        function addItem() {
            const tbody = document.querySelector('#items-table tbody');
            const row = document.createElement('tr');
            row.className = 'border-b border-zinc-100';
            row.innerHTML = `
                <td class="px-2 py-2"><input name="items[${itemIndex}][description]" class="w-72 rounded-md border-zinc-300"></td>
                <td class="px-2 py-2"><input type="number" step="0.001" name="items[${itemIndex}][quantity]" value="1" class="w-24 rounded-md border-zinc-300"></td>
                <td class="px-2 py-2"><input type="number" step="0.0001" name="items[${itemIndex}][unit_price]" class="w-28 rounded-md border-zinc-300"></td>
                <td class="px-2 py-2"><input type="number" step="0.01" name="items[${itemIndex}][tax_rate]" class="w-24 rounded-md border-zinc-300"></td>
                <td class="px-2 py-2"><input type="number" step="0.01" name="items[${itemIndex}][tax_amount]" class="w-28 rounded-md border-zinc-300"></td>
                <td class="px-2 py-2"><input type="number" step="0.01" name="items[${itemIndex}][total]" class="w-28 rounded-md border-zinc-300"></td>
                <td class="px-2 py-2"><button type="button" onclick="this.closest('tr').remove()" class="text-rose-700">Remover</button></td>
            `;
            tbody.appendChild(row);
            itemIndex++;
        }
    </script>
@endsection
