@extends('supplier-invoices._layout', ['title' => 'Enviar fatura'])

@section('content')
    <form method="POST" action="{{ route('supplier-invoices.store') }}" enctype="multipart/form-data"
          class="mx-auto max-w-3xl space-y-6 rounded-xl bg-white p-6 sm:p-8 shadow-sm ring-1 ring-slate-200">
        @csrf

        <div>
            <h2 class="text-lg font-semibold text-slate-900">Nova fatura de fornecedor</h2>
            <p class="mt-1 text-sm text-slate-500">Preenche os dados básicos e anexa os ficheiros — o OCR trata do resto.</p>
        </div>

        <div class="grid gap-5 md:grid-cols-3">
            <label class="block">
                <span class="text-sm font-medium text-slate-700">Marca</span>
                <select name="brand_id" required
                        class="mt-1.5 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Escolher marca</option>
                    @foreach($brands as $id => $name)
                        <option value="{{ $id }}" @selected(old('brand_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block md:col-span-2">
                <span class="text-sm font-medium text-slate-700">Para que foi</span>
                <input name="purpose" value="{{ old('purpose') }}" required
                       placeholder="Ex.: compra para stock, software, material de escritório"
                       class="mt-1.5 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>

            <label class="block">
                <span class="text-sm font-medium text-slate-700">Categoria</span>
                <select name="category" required
                        class="mt-1.5 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category', 'fornecedores') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block md:col-span-2">
                <span class="text-sm font-medium text-slate-700">Ficheiros da fatura</span>
                <input type="file" name="documents[]" required multiple accept=".pdf,.jpg,.jpeg,.png"
                       class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm text-slate-600 shadow-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-900 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-slate-700">
                <span class="mt-1.5 block text-xs text-slate-500">Podes selecionar várias fotos da mesma fatura. O sistema lê todas antes da revisão.</span>
            </label>

            <label class="block md:col-span-3">
                <span class="text-sm font-medium text-slate-700">Fotos extra <span class="font-normal text-slate-400">(opcional)</span></span>
                <input type="file" name="images[]" multiple accept=".jpg,.jpeg,.png"
                       class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white p-2 text-sm text-slate-600 shadow-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
            </label>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('supplier-invoices.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancelar</a>
            <button class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                Enviar e processar
            </button>
        </div>
    </form>
@endsection
