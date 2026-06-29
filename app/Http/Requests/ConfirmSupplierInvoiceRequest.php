<?php

namespace App\Http\Requests;

class ConfirmSupplierInvoiceRequest extends UpdateSupplierInvoiceReviewRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'total' => ['required', 'numeric', 'min:0'],
            'items.*.description' => ['required_with:items', 'string'],
        ]);
    }
}
