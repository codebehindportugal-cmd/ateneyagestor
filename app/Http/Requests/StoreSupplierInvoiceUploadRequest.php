<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierInvoiceUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $max = (int) config('purchase_invoices.max_upload_size', 10240);

        return [
            'brand_id' => ['required', 'exists:brands,id'],
            'purpose' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'documents' => ['required_without:file', 'array', 'min:1'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$max],
            'file' => ['required_without:documents', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$max],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png', 'max:'.$max],
        ];
    }
}
