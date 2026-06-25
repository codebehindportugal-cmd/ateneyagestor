<?php

namespace App\Http\Controllers;

use App\Models\AccountingDocument;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AccountantViewController extends Controller
{
    public function index(string $token)
    {
        $this->validateToken($token);

        $documents = AccountingDocument::orderByDesc('date')->get();

        $grouped = $documents
            ->groupBy('year')
            ->sortKeysDesc()
            ->map(fn ($yearDocs) => $yearDocs->groupBy('month')->sortKeysDesc());

        $yearTotals = $documents->groupBy('year')->map(fn ($docs) => [
            'count'  => $docs->count(),
            'amount' => $docs->sum('amount_cents') / 100,
        ]);

        $grandTotal = [
            'count'  => $documents->count(),
            'amount' => $documents->sum('amount_cents') / 100,
        ];

        return view('accountant.index', compact('token', 'grouped', 'yearTotals', 'grandTotal'));
    }

    public function download(string $token, int $id)
    {
        $this->validateToken($token);

        $doc = AccountingDocument::findOrFail($id);

        if (!$doc->file_path || !Storage::disk('public')->exists($doc->file_path)) {
            abort(404, 'Ficheiro não encontrado.');
        }

        return Storage::disk('public')->download(
            $doc->file_path,
            $doc->file_name ?? basename($doc->file_path)
        );
    }

    private function validateToken(string $token): void
    {
        $stored = Setting::get('accountant_token');

        if (!$stored || !hash_equals($stored, $token)) {
            abort(403, 'Acesso não autorizado. URL inválido ou revogado.');
        }
    }
}
