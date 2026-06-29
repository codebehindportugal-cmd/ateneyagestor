<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaperInvoice\PaperInvoiceExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaperInvoiceExtractionController extends Controller
{
    public function __invoke(Request $request, PaperInvoiceExtractor $extractor): JsonResponse
    {
        $data = $request->validate([
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,bmp,tiff', 'max:20480'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,bmp,tiff', 'max:20480'],
        ]);

        $upload = $data['document'] ?? $data['image'] ?? null;

        abort_unless($upload, 422, 'Carrega uma foto, imagem ou PDF da fatura.');

        $path = $upload->store('paper-invoices/tmp');
        $absolutePath = Storage::path($path);

        try {
            return response()->json($extractor->extract($absolutePath));
        } finally {
            Storage::delete($path);
        }
    }
}
