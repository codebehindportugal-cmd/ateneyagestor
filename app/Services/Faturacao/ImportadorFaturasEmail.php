<?php

namespace App\Services\Faturacao;

use App\Models\AccountingDocument;
use App\Services\PaperInvoice\PaperInvoiceExtractor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Vai a caixa das facturas, traz os anexos e cria um documento por cada um.
 *
 * Regra combinada: so' entram mensagens com anexo PDF ou imagem. O resto —
 * newsletters, respostas, avisos de cobranca sem documento — fica na caixa e
 * nao chega ao contabilista.
 *
 * Nada aqui apaga mensagens. Depois de importada, a mensagem e' marcada como
 * lida e, se houver pasta configurada, arrumada la'. O original fica sempre no
 * email, que e' onde ele tem valor legal.
 */
class ImportadorFaturasEmail
{
    public function __construct(
        private readonly PaperInvoiceExtractor $extractor,
    ) {
    }

    /**
     * Liga-se so' para confirmar que as credenciais e a pasta estao certas.
     *
     * @return array{ok: bool, mensagem: string, pastas?: list<string>, porLer?: int}
     */
    public function testar(): array
    {
        $caixa = new ImapMailbox($this->config());

        try {
            $caixa->ligar();

            $pastas = $caixa->pastas();
            $pasta = (string) $this->config()['folder'];
            $total = $caixa->escolherPasta($pasta, soLeitura: true);
            $porLer = count($caixa->procurar('UNSEEN'));

            return [
                'ok' => true,
                'mensagem' => sprintf(
                    'Ligacao OK a %s. A pasta "%s" tem %d mensagem(ns), %d por ler.',
                    $this->config()['host'],
                    $pasta,
                    $total,
                    $porLer,
                ),
                'pastas' => $pastas,
                'porLer' => $porLer,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'mensagem' => $e->getMessage()];
        } finally {
            $caixa->fechar();
        }
    }

    /**
     * @param  callable(string):void|null  $relatar  recebe cada passo, para o comando o escrever
     * @return array{mensagens: int, documentos: int, duplicados: int, semAnexo: int, erros: list<string>}
     */
    public function correr(
        ?int $dias = null,
        ?int $limite = null,
        bool $incluirLidas = false,
        ?callable $relatar = null,
    ): array {
        $config = $this->config();
        $relatar ??= static fn (string $linha) => null;

        $contas = [
            'mensagens' => 0,
            'documentos' => 0,
            'duplicados' => 0,
            'semAnexo' => 0,
            'erros' => [],
        ];

        $caixa = new ImapMailbox($config);
        $caixa->ligar();

        try {
            $caixa->escolherPasta((string) $config['folder']);

            $destino = trim((string) ($config['processed_folder'] ?? ''));

            if ($destino !== '') {
                $caixa->criarPasta($destino);
                // O CREATE muda a pasta seleccionada nalguns servidores.
                $caixa->escolherPasta((string) $config['folder']);
            }

            $desde = Carbon::now()->subDays(max(1, $dias ?? (int) $config['days']));
            $uids = $caixa->procurarDesde($desde, $incluirLidas);

            $maximo = max(1, $limite ?? (int) $config['max_messages']);
            $uids = array_slice($uids, 0, $maximo);

            $relatar(sprintf('%d mensagem(ns) a analisar desde %s.', count($uids), $desde->format('d/m/Y')));

            foreach ($uids as $uid) {
                $contas['mensagens']++;

                try {
                    $bruto = $caixa->mensagemEmBruto($uid);
                    $mensagem = MimeMessage::deBruto($bruto);

                    $anexos = $mensagem->anexosDeFatura(
                        minimoImagemBytes: max(0, (int) $config['min_image_kb']) * 1024,
                        maximoBytes: max(1, (int) $config['max_attachment_mb']) * 1024 * 1024,
                    );

                    if ($anexos === []) {
                        $contas['semAnexo']++;
                        $relatar(sprintf('  #%d "%s" — sem anexo de factura, deixada na caixa.', $uid, Str::limit($mensagem->assunto(), 50)));

                        continue;
                    }

                    $criados = 0;

                    foreach ($anexos as $anexo) {
                        $documento = $this->criarDocumento($mensagem, $anexo);

                        if ($documento === null) {
                            $contas['duplicados']++;
                            $relatar(sprintf('  #%d "%s" — ja tinha sido importado.', $uid, $anexo['nome']));

                            continue;
                        }

                        $criados++;
                        $contas['documentos']++;
                        $relatar(sprintf(
                            '  #%d %s -> documento %d (%s, %s)',
                            $uid,
                            $anexo['nome'],
                            $documento->id,
                            $documento->fornecedor ?: 'fornecedor por identificar',
                            number_format($documento->amount, 2, ',', '.').' EUR',
                        ));
                    }

                    // Uma mensagem cujos anexos ja estavam todos importados
                    // tambem se arruma: caso contrario voltava a ser analisada
                    // em todas as corridas ate sair da janela de dias.
                    $caixa->marcarLida($uid);

                    if ($destino !== '') {
                        $caixa->mover($uid, $destino);
                    }
                } catch (\Throwable $e) {
                    $erro = sprintf('Mensagem #%d: %s', $uid, $e->getMessage());
                    $contas['erros'][] = $erro;
                    $relatar('  ERRO '.$erro);
                    Log::warning('faturas:importar-email — '.$erro, ['excepcao' => $e]);
                }
            }
        } finally {
            $caixa->fechar();
        }

        return $contas;
    }

    // ── Criacao do documento ─────────────────────────────────────────────────

    /**
     * @param  array{nome: string, mime: string, conteudo: string, extensao: string}  $anexo
     */
    private function criarDocumento(MimeMessage $mensagem, array $anexo): ?AccountingDocument
    {
        $hash = hash('sha256', $anexo['conteudo']);

        if (AccountingDocument::where('ficheiro_hash', $hash)->exists()) {
            return null;
        }

        $recebidoEm = $mensagem->data() ? Carbon::instance($mensagem->data()) : Carbon::now();
        $ehPdf = $anexo['extensao'] === 'pdf';

        $pasta = ($ehPdf ? 'accounting-documents' : 'accounting-document-images')
            .'/email/'.$recebidoEm->format('Y/m');

        $nomeFicheiro = Str::limit(Str::slug(pathinfo($anexo['nome'], PATHINFO_FILENAME)), 60, '')
            .'-'.substr($hash, 0, 8).'.'.$anexo['extensao'];

        $caminho = $pasta.'/'.$nomeFicheiro;

        Storage::disk('public')->put($caminho, $anexo['conteudo']);

        $leitura = $this->ler($caminho);

        $fornecedorLido = trim((string) ($leitura['supplier']['name'] ?? ''));
        $nif = trim((string) ($leitura['supplier']['taxNumber'] ?? ''));
        $factura = $leitura['invoice'] ?? [];

        $documento = new AccountingDocument();

        $documento->fill([
            'tipo' => $this->tipoDeDocumento($factura['type'] ?? null),
            // A finalidade e' uma decisao de quem gere, nao se le da factura.
            // Fica em "Outro" e o painel mostra-a como por classificar.
            'title' => 'outro',
            'estado' => 'pendente',
            'invoice_number' => $factura['number'] ?: null,
            'supplier_nif' => $nif ?: null,
            'atcud' => $factura['atcud'] ?: null,
            'fornecedor' => AccountingDocument::fornecedorPorNif($nif)
                ?: ($fornecedorLido ?: $mensagem->nomeDe()),
            'date' => $this->data($factura['date'] ?? null, $recebidoEm),
            'amount_cents' => (int) round(((float) ($factura['total'] ?? 0)) * 100),
            'iva_cents' => (int) round(((float) ($factura['vatTotal'] ?? 0)) * 100),
            'currency' => $factura['currency'] ?? 'EUR',
            'category' => 'fornecedores',
            'brand_id' => $this->marcaPorDefeito(),
            'products' => $this->produtos($leitura['products'] ?? []),
            'notes' => $this->notas($mensagem, $leitura),
            'origem' => 'email',
            'importado_contabilidade' => false,
            'email_message_id' => $mensagem->messageId(),
            'email_de' => Str::limit($mensagem->de(), 250, ''),
            'email_assunto' => Str::limit($mensagem->assunto(), 250, ''),
            'email_recebido_em' => $recebidoEm,
            'ficheiro_hash' => $hash,
        ]);

        if ($ehPdf) {
            $documento->file_path = $caminho;
            $documento->file_name = $anexo['nome'];
        } else {
            $documento->image_paths = [$caminho];
            $documento->image_names = [$anexo['nome']];
        }

        $documento->save();

        return $documento;
    }

    /**
     * A leitura do PDF/foto nunca pode derrubar a importacao: sem o poppler ou
     * o tesseract instalados o extractor devolve tudo vazio, e mesmo assim o
     * documento tem de entrar — com o ficheiro anexado, para ser corrigido a
     * mao. Perder a factura era pior do que a ter com campos por preencher.
     */
    private function ler(string $caminhoRelativo): array
    {
        try {
            return $this->extractor->extract(Storage::disk('public')->path($caminhoRelativo));
        } catch (\Throwable $e) {
            Log::warning('faturas:importar-email — leitura falhou: '.$e->getMessage());

            return [
                'supplier' => [],
                'invoice' => [],
                'products' => [],
                'warnings' => ['A leitura automatica falhou: '.$e->getMessage()],
                'rawText' => '',
            ];
        }
    }

    private function tipoDeDocumento(?string $tipo): string
    {
        return match ($tipo) {
            'NC' => 'nota_credito',
            'RC' => 'recibo',
            default => 'fatura',
        };
    }

    private function data(?string $data, Carbon $alternativa): string
    {
        if (! $data) {
            return $alternativa->toDateString();
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, $data)->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        return $alternativa->toDateString();
    }

    private function produtos(array $produtos): array
    {
        return collect($produtos)
            ->map(fn (array $produto) => [
                'description' => (string) ($produto['description'] ?? ''),
                'quantity' => (float) ($produto['quantity'] ?? 1),
                'unitPrice' => (float) ($produto['unitPrice'] ?? 0),
                'vatRate' => (float) ($produto['vatRate'] ?? 0),
                'lineTotal' => (float) ($produto['lineTotal'] ?? 0),
                'confidence' => round((float) ($produto['confidence'] ?? 0), 2),
            ])
            ->filter(fn (array $produto) => trim($produto['description']) !== '')
            ->values()
            ->all();
    }

    private function notas(MimeMessage $mensagem, array $leitura): string
    {
        $blocos = [];

        $blocos[] = 'Importado automaticamente do email.'
            ."\nDe: ".($mensagem->de() ?: '(desconhecido)')
            ."\nAssunto: ".($mensagem->assunto() ?: '(sem assunto)')
            ."\nRecebido: ".($mensagem->data()?->format('d/m/Y H:i') ?? '(sem data)');

        if (($leitura['warnings'] ?? []) !== []) {
            $blocos[] = "Avisos da leitura:\n- ".implode("\n- ", $leitura['warnings']);
        }

        if (($leitura['rawText'] ?? '') !== '') {
            $blocos[] = "Texto lido:\n".mb_substr((string) $leitura['rawText'], 0, 4000);
        }

        return implode("\n\n", $blocos);
    }

    private function marcaPorDefeito(): ?int
    {
        $marca = $this->config()['default_brand_id'] ?? null;

        return is_numeric($marca) ? (int) $marca : null;
    }

    private function config(): array
    {
        return (array) config('faturas_email', []);
    }
}
