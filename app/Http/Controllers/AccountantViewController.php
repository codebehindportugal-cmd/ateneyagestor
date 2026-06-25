<?php

namespace App\Http\Controllers;

use App\Models\AccountingDocument;
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
