<?php

namespace App\Http\Controllers;

use App\Models\ClientDocument;
use App\Services\ClientDocumentService;

class ClientDocumentController extends Controller
{
    public function __construct(private ClientDocumentService $service) {}

    /**
     * Serve the document to an authenticated admin.
     * ?inline=1 → open in browser; default → download as attachment.
     */
    public function show(ClientDocument $document)
    {
        $inline = request()->boolean('inline');

        return $this->service->stream($document, $inline);
    }
}
