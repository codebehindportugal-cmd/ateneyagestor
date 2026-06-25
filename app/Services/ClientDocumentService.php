<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientDocument;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientDocumentService
{
    public function __construct(private NasService $nas) {}

    /**
     * Move a temp-uploaded file to its final location (NAS or local public),
     * create and return the ClientDocument record.
     *
     * @param  string $tempDiskPath  Path relative to the 'local' disk (e.g. 'tmp-client-docs/abc.pdf')
     */
    public function processUpload(
        Client $client,
        string $tempDiskPath,
        string $originalName,
        string $type,
        string $documentName,
        ?int $uploadedBy,
    ): ClientDocument {
        $tempAbsPath = Storage::disk('local')->path($tempDiskPath);

        if (! file_exists($tempAbsPath)) {
            throw new \RuntimeException("Ficheiro temporário não encontrado: {$tempAbsPath}");
        }

        $year      = now()->year;
        $slug      = Str::slug($client->company ?: $client->name) ?: "cliente-{$client->id}";
        $ext       = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $filename  = Str::uuid() . ($ext ? ".{$ext}" : '');
        $mimeType  = @mime_content_type($tempAbsPath) ?: 'application/octet-stream';
        $fileSize  = (int) filesize($tempAbsPath);

        if ($this->nas->isConfigured()) {
            $subDir  = "clientes/{$slug}/documentos/{$year}";
            $nasPath = $this->nas->upload($tempAbsPath, $subDir, $filename);
            @unlink($tempAbsPath);

            return ClientDocument::create([
                'client_id'    => $client->id,
                'name'         => $documentName,
                'type'         => $type,
                'file_path'    => $nasPath,
                'storage_type' => 'nas',
                'original_name' => $originalName,
                'file_size'    => $fileSize,
                'mime_type'    => $mimeType,
                'uploaded_by'  => $uploadedBy,
            ]);
        }

        // Fallback: store on local public disk
        $subDir  = "client-documents/{$slug}/{$year}";
        $destDir = storage_path("app/public/{$subDir}");
        @mkdir($destDir, 0775, true);
        rename($tempAbsPath, "{$destDir}/{$filename}");

        return ClientDocument::create([
            'client_id'    => $client->id,
            'name'         => $documentName,
            'type'         => $type,
            'file_path'    => "{$subDir}/{$filename}",
            'storage_type' => 'local',
            'original_name' => $originalName,
            'file_size'    => $fileSize,
            'mime_type'    => $mimeType,
            'uploaded_by'  => $uploadedBy,
        ]);
    }

    /**
     * Stream the document to the browser.
     * $inline = true → opens in browser (PDF/image preview)
     * $inline = false → forces download attachment
     */
    public function stream(ClientDocument $doc, bool $inline = false): StreamedResponse
    {
        $disposition = $inline ? 'inline' : 'attachment';
        $headers = [
            'Content-Type'        => $doc->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => "{$disposition}; filename=\"{$doc->original_name}\"",
            'Content-Length'      => $doc->file_size,
        ];

        if ($doc->storage_type === 'nas') {
            $tmpFile = $this->nas->downloadToTemp($doc->file_path);
            return response()->stream(function () use ($tmpFile) {
                readfile($tmpFile);
                @unlink($tmpFile);
            }, Response::HTTP_OK, $headers);
        }

        // Local public disk
        $absPath = storage_path("app/public/{$doc->file_path}");

        if (! file_exists($absPath)) {
            abort(404, 'Ficheiro não encontrado.');
        }

        return response()->stream(fn () => readfile($absPath), Response::HTTP_OK, $headers);
    }

    /**
     * Delete the file from storage AND the DB record.
     */
    public function delete(ClientDocument $doc): void
    {
        if ($doc->storage_type === 'nas') {
            try {
                $this->nas->deleteFile($doc->file_path);
            } catch (\Throwable $e) {
                logger()->warning("ClientDocumentService: falhou ao apagar ficheiro NAS {$doc->file_path}", [
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            @unlink(storage_path("app/public/{$doc->file_path}"));
        }

        $doc->delete();
    }
}
