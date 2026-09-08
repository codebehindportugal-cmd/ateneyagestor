<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\AccountingDocument;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TicketMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Guarda e serve os ficheiros agarrados a projectos e tarefas.
 *
 * Segue a mesma regra do ClientDocumentService — NAS quando esta configurado,
 * disco do servidor quando nao — de proposito: dois servicos com politicas de
 * armazenamento diferentes davam duas pastas de backup a manter e duas formas
 * de perder ficheiros.
 */
class AttachmentService
{
    public function __construct(private NasService $nas)
    {
    }

    /**
     * Pega num ficheiro acabado de carregar (disco `local`, pasta temporaria) e
     * arruma-o no sitio definitivo.
     *
     * @param  string  $tempDiskPath  caminho relativo ao disco 'local'
     */
    public function processUpload(
        Model $attachable,
        string $tempDiskPath,
        string $originalName,
        string $name,
        string $origem = 'cliente',
        ?string $notes = null,
        ?int $uploadedBy = null,
    ): Attachment {
        $tempAbsPath = Storage::disk('local')->path($tempDiskPath);

        if (! file_exists($tempAbsPath)) {
            throw new \RuntimeException("Ficheiro temporário não encontrado: {$tempAbsPath}");
        }

        $extensao = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $ficheiro = Str::uuid().($extensao ? ".{$extensao}" : '');
        $mimeType = @mime_content_type($tempAbsPath) ?: 'application/octet-stream';
        $tamanho = (int) filesize($tempAbsPath);
        $subDir = $this->pastaDe($attachable);

        $comuns = [
            'name' => $name !== '' ? $name : $originalName,
            'original_name' => $originalName,
            'file_size' => $tamanho,
            'mime_type' => $mimeType,
            'origem' => $origem,
            'notes' => $notes,
            'uploaded_by' => $uploadedBy,
        ];

        // `isConfigured()` diz que o .env tem os valores, nao que a maquina
        // responde. A 09/09/2026 o NAS estava configurado e inalcancavel — o
        // tunel WireGuard em baixo, `ssh: connect to host 10.0.0.2 port 22:
        // Connection timed out` — e os anexos das facturas simplesmente nao
        // entravam. Um ficheiro do cliente no disco do servidor e' pior do que
        // no NAS; perdido nao ha comparacao. Quando o NAS nao atende, grava-se
        // aqui e segue-se.
        if ($this->nas->isConfigured()) {
            try {
                $caminho = $this->nas->upload($tempAbsPath, $subDir, $ficheiro);
                @unlink($tempAbsPath);

                return $attachable->anexos()->create($comuns + [
                    'file_path' => $caminho,
                    'storage_type' => 'nas',
                ]);
            } catch (\Throwable $e) {
                Log::warning(
                    'AttachmentService: NAS indisponivel, guardei no disco do servidor. '.$e->getMessage()
                );
            }
        }

        $destino = storage_path("app/public/{$subDir}");
        @mkdir($destino, 0775, true);
        rename($tempAbsPath, "{$destino}/{$ficheiro}");

        return $attachable->anexos()->create($comuns + [
            'file_path' => "{$subDir}/{$ficheiro}",
            'storage_type' => 'local',
        ]);
    }

    /**
     * Os ficheiros de uma tarefa ficam debaixo do projecto dela, nao numa
     * pasta "tarefas" a parte: quem for ao NAS procurar as fotos de um cliente
     * quer tudo junto, sem ter de saber a que tarefa pertenciam.
     */
    private function pastaDe(Model $attachable): string
    {
        $ano = now()->year;

        if ($attachable instanceof Project) {
            return "projectos/{$attachable->slug}/anexos/{$ano}";
        }

        if ($attachable instanceof ProjectTask) {
            $slug = $attachable->project?->slug ?: 'sem-projecto';

            return "projectos/{$slug}/anexos/{$ano}/tarefa-{$attachable->id}";
        }

        // Os anexos de um ticket ficam debaixo do cliente e do ticket, nao
        // da mensagem: quem procura "a foto que o cliente mandou naquele
        // problema" nao se lembra em que resposta da conversa ela vinha.
        if ($attachable instanceof TicketMessage) {
            $ticket = $attachable->ticket;
            $cliente = $ticket?->client;
            $slug = $cliente
                ? (Str::slug($cliente->company ?: $cliente->name) ?: "cliente-{$cliente->id}")
                : 'sem-cliente';

            return "clientes/{$slug}/tickets/{$ticket?->id}";
        }

        // Os acompanhantes de uma factura (detalhe, CSV, XML) ficam ao lado
        // dela, por ano e mes — a mesma arrumacao que o contabilista ve.
        if ($attachable instanceof AccountingDocument) {
            $data = $attachable->date ?? now();

            return 'contabilidade/'.$data->format('Y/m').'/anexos';
        }

        $tipo = Str::slug(class_basename($attachable));

        return "anexos/{$tipo}/{$attachable->getKey()}/{$ano}";
    }

    /** $inline = true abre no browser (imagens e PDF); false forca o download. */
    public function stream(Attachment $anexo, bool $inline = false): StreamedResponse
    {
        $disposicao = $inline ? 'inline' : 'attachment';

        $cabecalhos = [
            'Content-Type' => $anexo->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => "{$disposicao}; filename=\"{$anexo->original_name}\"",
            'Content-Length' => $anexo->file_size,
        ];

        if ($anexo->storage_type === 'nas') {
            $temporario = $this->nas->downloadToTemp($anexo->file_path);

            return response()->stream(function () use ($temporario) {
                readfile($temporario);
                @unlink($temporario);
            }, Response::HTTP_OK, $cabecalhos);
        }

        $absoluto = storage_path("app/public/{$anexo->file_path}");

        if (! file_exists($absoluto)) {
            abort(404, 'Ficheiro não encontrado.');
        }

        return response()->stream(fn () => readfile($absoluto), Response::HTTP_OK, $cabecalhos);
    }

    /**
     * Apaga so' o ficheiro. O registo e' apagado por quem chamou — e' o
     * `deleting` do modelo que chama isto, e apagar la dentro dava recursao.
     *
     * Uma falha a apagar nao rebenta nada: o registo tem de desaparecer na
     * mesma, senao fica uma linha a apontar para um ficheiro que ja nao existe.
     */
    public function apagarFicheiro(Attachment $anexo): void
    {
        try {
            if ($anexo->storage_type === 'nas') {
                $this->nas->deleteFile($anexo->file_path);

                return;
            }

            @unlink(storage_path("app/public/{$anexo->file_path}"));
        } catch (\Throwable $e) {
            logger()->warning("AttachmentService: nao consegui apagar {$anexo->file_path}", [
                'erro' => $e->getMessage(),
            ]);
        }
    }
}
