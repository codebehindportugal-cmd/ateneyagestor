<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\Gate;

/**
 * Os anexos nunca ficam num URL publico.
 *
 * Um ficheiro em `storage/app/public` fica acessivel a quem adivinhe o
 * endereco, e as fotos que os clientes mandam nao sao para andar por ai. Saem
 * todos por aqui: sessao iniciada, politica verificada, e so' entao o
 * ficheiro — venha ele do disco ou do NAS.
 */
class AttachmentController extends Controller
{
    public function ver(Attachment $attachment, AttachmentService $anexos)
    {
        Gate::authorize('view', $attachment);

        return $anexos->stream($attachment, inline: true);
    }

    public function download(Attachment $attachment, AttachmentService $anexos)
    {
        Gate::authorize('view', $attachment);

        return $anexos->stream($attachment, inline: false);
    }
}
