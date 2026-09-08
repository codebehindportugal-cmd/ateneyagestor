<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\TicketMessage;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Os anexos nunca ficam num URL publico.
 *
 * Um ficheiro em `storage/app/public` fica acessivel a quem adivinhe o
 * endereco, e as fotos que os clientes mandam nao sao para andar por ai. Saem
 * todos por aqui: sessao iniciada, permissao verificada, e so' entao o
 * ficheiro — venha ele do disco ou do NAS.
 *
 * Sao duas portas porque sao duas sessoes diferentes: a equipa entra pelo
 * guard `web` e o cliente pelo guard `client` (ver config/auth.php). A politica
 * do Laravel recebe um `User` e nao serve para o cliente — dai a verificacao
 * a mao no lado dele, que e' tambem mais estreita: so' os anexos das mensagens
 * dos tickets dele.
 */
class AttachmentController extends Controller
{
    // ── Equipa (guard `web`) ─────────────────────────────────────────────────

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

    // ── Cliente (guard `client`) ─────────────────────────────────────────────

    public function verCliente(Attachment $attachment, AttachmentService $anexos)
    {
        $this->confirmarQueEDoCliente($attachment);

        return $anexos->stream($attachment, inline: true);
    }

    public function downloadCliente(Attachment $attachment, AttachmentService $anexos)
    {
        $this->confirmarQueEDoCliente($attachment);

        return $anexos->stream($attachment, inline: false);
    }

    /**
     * O cliente so' chega aos ficheiros das mensagens dos tickets dele.
     *
     * Deliberadamente estreito: nao ha aqui nenhum caminho para os anexos de
     * projectos ou de tarefas, que sao trabalho interno. Se um dia houver, tem
     * de ser uma decisao tomada de proposito e nao um efeito lateral desta
     * funcao.
     */
    private function confirmarQueEDoCliente(Attachment $attachment): void
    {
        $cliente = Auth::guard('client')->user();

        abort_unless($cliente !== null, 403);

        $mensagem = $attachment->attachable;

        abort_unless($mensagem instanceof TicketMessage, 403, 'Este ficheiro não pertence a um ticket.');
        abort_unless($mensagem->ticket?->client_id === $cliente->getKey(), 403);
    }
}
