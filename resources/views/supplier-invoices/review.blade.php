@extends('supplier-invoices._layout', ['title' => 'Rever fatura'])

@section('content')
    <form method="POST" action="{{ route('supplier-invoices.update', $invoice) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Dados principais</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Estado: <span class="font-medium text-slate-700">{{ $invoice->status }}</span>@if($invoice->error_message) · <span class="text-rose-600">{{ $invoice->error_message }}</span>@endif</p>
                </div>
                <a class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50"
                   href="{{ route('supplier-invoices.download', $invoice) }}">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Abrir ficheiro
                </a>
            </div>

            @php $inputClass = 'mt-1.5 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500'; @endphp

            <div class="grid gap-5 md:grid-cols-4">
                <label class="block"><span class="text-sm font-medium text-slate-700">Marca</span><select name="brand_id" class="{{ $inputClass }}">@foreach($brands as $id => $name)<option value="{{ $id }}" @selected(old('brand_id', $invoice->brand_id) == $id)>{{ $name }}</option>@endforeach</select></label>
                <label class="block md:col-span-2"><span class="text-sm font-medium text-slate-700">Para que foi</span><input name="purpose" value="{{ old('purpose', $invoice->purpose) }}" class="{{ $inputClass }}"></label>
                <label class="block"><span class="text-sm font-medium text-slate-700">Categoria</span><select name="category" class="{{ $inputClass }}">@foreach($categories as $key => $label)<option value="{{ $key }}" @selected(old('category', $invoice->category) === $key)>{{ $label }}</option>@endforeach</select></label>
                <label class="block"><span class="text-sm font-medium text-slate-700">Fornecedor</span><input name="supplier_name" value="{{ old('supplier_name', $invoice->supplier_name) }}" class="{{ $inputClass }}"></label>
                <label class="block"><span class="text-sm font-medium text-slate-700">NIF</span><input name="supplier_tax_number" value="{{ old('supplier_tax_number', $invoice->supplier_tax_number) }}" class="{{ $inputClass }}"></label>
                <label class="block"><span class="text-sm font-medium text-slate-700">Nº fatura</span><input name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}" class="{{ $inputClass }}"></label>
                <label class="block"><span class="text-sm font-medium text-slate-700">Moeda</span><input name="currency" maxlength="3" value="{{ old('currency', $invoice->currency) }}" class="{{ $inputClass }}"></label>
                <label class="block"><span class="text-sm font-medium text-slate-700">Data</span><input type="date" name="invoice_date" value="{{ old('invoice_date', $invoice->invoice_date?->format('Y-m-d')) }}" class="{{ $inputClass }}"></label>
                <label class="block"><span class="text-sm font-medium text-slate-700">Vencimento</span><input type="date" name="due_date" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}" class="{{ $inputClass }}"></label>
                <label class="block"><span class="text-sm font-medium text-slate-700">Subtotal</span><input type="number" step="0.01" name="subtotal" value="{{ old('subtotal', $invoice->subtotal) }}" class="{{ $inputClass }}"></label>
                <label class="block"><span class="text-sm font-medium text-slate-700">IVA</span><input type="number" step="0.01" name="tax_total" value="{{ old('tax_total', $invoice->tax_total) }}" class="{{ $inputClass }}"></label>
                <label class="block"><span class="text-sm font-medium text-slate-700">Total</span><input type="number" step="0.01" name="total" value="{{ old('total', $invoice->total) }}" class="{{ $inputClass }}"></label>
            </div>
        </section>

        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Itens</h2>
                <button type="button" onclick="addItem()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Adicionar item
                </button>
            </div>
            <div class="overflow-x-auto rounded-lg ring-1 ring-slate-200">
                <table class="w-full text-sm" id="items-table">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-left text-[11px] uppercase tracking-wider text-slate-500">
                            <th class="px-3 py-2.5 font-semibold">Descrição</th>
                            <th class="px-3 py-2.5 font-semibold">Qtd.</th>
                            <th class="px-3 py-2.5 font-semibold">Preço</th>
                            <th class="px-3 py-2.5 font-semibold">IVA %</th>
                            <th class="px-3 py-2.5 font-semibold">Valor IVA</th>
                            <th class="px-3 py-2.5 font-semibold">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(($items ?: [[]]) as $i => $item)
                            <tr class="border-b border-slate-100">
                                <td class="px-3 py-2"><input name="items[{{ $i }}][description]" value="{{ $item['description'] ?? '' }}" class="w-72 rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></td>
                                <td class="px-3 py-2"><input type="number" step="0.001" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" class="w-24 rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></td>
                                <td class="px-3 py-2"><input type="number" step="0.0001" name="items[{{ $i }}][unit_price]" value="{{ $item['unit_price'] ?? '' }}" class="w-28 rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" name="items[{{ $i }}][tax_rate]" value="{{ $item['tax_rate'] ?? '' }}" class="w-24 rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" name="items[{{ $i }}][tax_amount]" value="{{ $item['tax_amount'] ?? '' }}" class="w-28 rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" name="items[{{ $i }}][total]" value="{{ $item['total'] ?? '' }}" class="w-28 rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></td>
                                <td class="px-3 py-2"><button type="button" onclick="this.closest('tr').remove()" class="text-sm font-medium text-rose-600 hover:text-rose-800">Remover</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <details>
                <summary class="cursor-pointer font-semibold text-slate-800 select-none">Texto extraído (OCR)</summary>
                <textarea readonly class="mt-3 h-64 w-full rounded-lg border-slate-300 bg-slate-50 font-mono text-xs text-slate-600 shadow-sm">{{ $invoice->raw_extracted_text }}</textarea>
            </details>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <button class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50">Guardar revisão</button>
            <button formaction="{{ route('supplier-invoices.confirm', $invoice) }}" formmethod="POST"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Confirmar importação
            </button>
        </div>
    </form>

    <script>
        let itemIndex = {{ max(count($items), 1) }};
        const itemInputClass = 'rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
        function addItem() {
            const tbody = document.querySelector('#items-table tbody');
            const row = document.createElement('tr');
            row.className = 'border-b border-slate-100';
            row.innerHTML = `
                <td class="px-3 py-2"><input name="items[${itemIndex}][description]" class="w-72 ${itemInputClass}"></td>
                <td class="px-3 py-2"><input type="number" step="0.001" name="items[${itemIndex}][quantity]" value="1" class="w-24 ${itemInputClass}"></td>
                <td class="px-3 py-2"><input type="number" step="0.0001" name="items[${itemIndex}][unit_price]" class="w-28 ${itemInputClass}"></td>
                <td class="px-3 py-2"><input type="number" step="0.01" name="items[${itemIndex}][tax_rate]" class="w-24 ${itemInputClass}"></td>
                <td class="px-3 py-2"><input type="number" step="0.01" name="items[${itemIndex}][tax_amount]" class="w-28 ${itemInputClass}"></td>
                <td class="px-3 py-2"><input type="number" step="0.01" name="items[${itemIndex}][total]" class="w-28 ${itemInputClass}"></td>
                <td class="px-3 py-2"><button type="button" onclick="this.closest('tr').remove()" class="text-sm font-medium text-rose-600 hover:text-rose-800">Remover</button></td>
            `;
            tbody.appendChild(row);
            itemIndex++;
        }
    </script>
@endsection
