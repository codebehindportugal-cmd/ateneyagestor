<?php

namespace App\Http\Controllers;

use App\Models\AccountingDocument;
use App\Models\Brand;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\Setting;
use App\Services\ClientDocumentService;
use Illuminate\Support\Facades\Storage;

class AccountantViewController extends Controller
{
    // ── Global accountant view (AccountingDocuments) ──────────────────────────

    public function index(string $token)
    {
        $this->validateGlobalToken($token);

        $documents = AccountingDocument::with('brand.parent')->orderByDesc('date')->get();

        $brandGroups = $documents
            ->groupBy(fn ($d) => $d->brand_id ?? 0)
            ->sortBy(fn ($docs, $brandId) => $brandId === 0
                ? 'ZZZZ'
                : ($docs->first()->brand?->full_name ?? 'ZZZZ')
            )
            ->map(fn ($brandDocs, $brandId) => [
                'brand'      => $brandId ? $brandDocs->first()->brand : null,
                'grouped'    => $brandDocs->groupBy('year')->sortKeysDesc()
                                    ->map(fn ($yearDocs) => $yearDocs->groupBy('month')->sortKeysDesc()),
                'yearTotals' => $brandDocs->groupBy('year')->map(fn ($docs) => [
                    'count'  => $docs->count(),
                    'amount' => $docs->sum('amount_cents') / 100,
                ]),
                'total'      => [
                    'count'  => $brandDocs->count(),
                    'amount' => $brandDocs->sum('amount_cents') / 100,
                ],
            ]);

        $grandTotal = [
            'count'  => $documents->count(),
            'amount' => $documents->sum('amount_cents') / 100,
        ];

        return view('accountant.index', compact('token', 'brandGroups', 'grandTotal'));
    }

    public function download(string $token, int $id)
    {
        $this->validateGlobalToken($token);

        $doc = AccountingDocument::findOrFail($id);

        if (! $doc->file_path || ! Storage::disk('public')->exists($doc->file_path)) {
            abort(404, 'Ficheiro não encontrado.');
        }

        return Storage::disk('public')->download(
            $doc->file_path,
            $doc->file_name ?? basename($doc->file_path)
        );
    }

    // ── Per-client accountant view (ClientDocuments) ─────────────────────────

    public function clientIndex(string $token)
    {
        $client = $this->validateClientToken($token);

        $documents = $client->documents()->with('uploader')->get();

        $grouped = $documents
            ->groupBy('type')
            ->sortKeys();

        return view('accountant.client-documents', compact(
            'token', 'client', 'documents', 'grouped'
        ));
    }

    public function clientDocument(string $token, ClientDocument $document)
    {
        $client = $this->validateClientToken($token);

        if ($document->client_id !== $client->id) {
            abort(403, 'Acesso não autorizado.');
        }

        return app(ClientDocumentService::class)->stream($document, inline: true);
    }

    public function clientDownload(string $token, ClientDocument $document)
    {
        $client = $this->validateClientToken($token);

        if ($document->client_id !== $client->id) {
            abort(403, 'Acesso não autorizado.');
        }

        return app(ClientDocumentService::class)->stream($document, inline: false);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validateGlobalToken(string $token): void
    {
        $stored = Setting::get('accountant_token');

        if (! $stored || ! hash_equals($stored, $token)) {
            abort(403, 'Acesso não autorizado. URL inválido ou revogado.');
        }
    }

    private function validateClientToken(string $token): Client
    {
        $client = Client::where('accountant_token', $token)->first();

        if (! $client) {
            abort(403, 'Acesso não autorizado. URL inválido ou revogado.');
        }

        return $client;
    }
}
