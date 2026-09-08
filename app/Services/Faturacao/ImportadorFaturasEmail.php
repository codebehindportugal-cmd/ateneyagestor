<?php

namespace App\Services\Faturacao;

use App\Models\AccountingDocument;
use App\Services\AttachmentService;
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
     * @return array{mensagens: int, documentos: int, porRever: int, anexos: int, duplicados: int, semAnexo: int, erros: list<string>}
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
            'porRever' => 0,
            'anexos' => 0,
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

                    $resultado = $this->processarMensagem($mensagem, $anexos, $uid, $relatar);

                    $contas['documentos'] += $resultado['documentos'];
                    $contas['porRever'] += $resultado['porRever'];
                    $contas['duplicados'] += $resultado['duplicados'];
                    $contas['anexos'] += $resultado['anexos'];

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

    // ── Uma mensagem, um gasto ───────────────────────────────────────────────

    /**
     * Uma mensagem pode trazer tres ficheiros e ser um so' gasto.
     *
     * O email da Via Verde traz a factura, o detalhe das passagens e um CSV.
     * Ate 09/09/2026 cada PDF virava um documento e o CSV era deitado fora: o
     * contabilista ficava com duas linhas para o mesmo gasto, uma delas a zero,
     * e sem o ficheiro de detalhe.
     *
     * A regra e' **ler**, nao adivinhar pelo nome: um ficheiro com total e' uma
     * factura, o resto sao anexos dela. No caso da Via Verde e' precisamente o
     * `detalhe_*.pdf` que traz o total — uma regra por nomes teria posto de
     * lado o unico ficheiro que interessava.
     *
     * Dois emails com duas facturas a serio continuam a dar dois documentos:
     * quem decide e' o total, nao a contagem de ficheiros.
     *
     * @param  list<array{nome: string, mime: string, conteudo: string, extensao: string, legivel: bool}>  $anexos
     * @return array{documentos: int, porRever: int, duplicados: int, anexos: int}
     */
    private function processarMensagem(MimeMessage $mensagem, array $anexos, int $uid, callable $relatar): array
    {
        $contas = ['documentos' => 0, 'porRever' => 0, 'duplicados' => 0, 'anexos' => 0];
        $recebidoEm = $mensagem->data() ? Carbon::instance($mensagem->data()) : Carbon::now();

        /** @var list<AccountingDocument> $documentos */
        $documentos = [];
        /** @var list<array{anexo: array, temp: string}> $porAnexar */
        $porAnexar = [];

        foreach ($anexos as $anexo) {
            $hash = hash('sha256', $anexo['conteudo']);

            if (AccountingDocument::where('ficheiro_hash', $hash)->exists()) {
                $contas['duplicados']++;
                $relatar(sprintf('  #%d %s — ja tinha sido importado.', $uid, $anexo['nome']));

                continue;
            }

            $temporario = $this->guardarTemporario($anexo);

            if (! $anexo['legivel']) {
                $porAnexar[] = ['anexo' => $anexo, 'temp' => $temporario, 'leitura' => null];

                continue;
            }

            $leitura = $this->ler($temporario);
            $total = (int) round(((float) ($leitura['invoice']['total'] ?? 0)) * 100);

            if ($total <= 0) {
                // Sem total nao e' a factura — e' o detalhe, a capa, o aviso.
                // A leitura vai junto: se no fim nenhum ficheiro tiver total, e'
                // dela que sai o texto lido para as Notas. Sem isso, o documento
                // que mais precisa de diagnostico era o unico que nao trazia
                // nenhum — foi o que aconteceu com o primeiro email da Via Verde.
                $porAnexar[] = ['anexo' => $anexo, 'temp' => $temporario, 'leitura' => $leitura];

                continue;
            }

            $documento = $this->criarDocumento($mensagem, $anexo, $temporario, $hash, $leitura, $total, $recebidoEm);
            $documentos[] = $documento;
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

        // Nenhum ficheiro tinha total: nao se deita nada fora. Nasce um
        // documento por rever com tudo agarrado, para alguem olhar.
        if ($documentos === [] && $porAnexar !== []) {
            $primeiroLegivel = null;

            foreach ($porAnexar as $indice => $pendente) {
                if ($pendente['anexo']['legivel']) {
                    $primeiroLegivel = $indice;

                    break;
                }
            }

            $documento = $this->criarDocumentoPorRever(
                $mensagem,
                $primeiroLegivel !== null ? $porAnexar[$primeiroLegivel] : null,
                $recebidoEm,
                $anexos,
                $porAnexar,
            );

            if ($primeiroLegivel !== null) {
                unset($porAnexar[$primeiroLegivel]);
                $porAnexar = array_values($porAnexar);
            }

            $documentos[] = $documento;
            $contas['documentos']++;
            $contas['porRever']++;

            $relatar(sprintf(
                '  #%d "%s" -> documento %d  << POR REVER (nenhum ficheiro tinha total)',
                $uid,
                Str::limit($mensagem->assunto(), 40),
                $documento->id,
            ));
        }

        // Os acompanhantes vao todos para o primeiro documento da mensagem: e'
        // o gasto a que pertencem, e o contabilista tem de os ter.
        if ($documentos !== [] && $porAnexar !== []) {
            $dono = $documentos[0];

            foreach ($porAnexar as $pendente) {
                try {
                    app(AttachmentService::class)->processUpload(
                        attachable: $dono,
                        tempDiskPath: $pendente['temp'],
                        originalName: $pendente['anexo']['nome'],
                        name: pathinfo($pendente['anexo']['nome'], PATHINFO_FILENAME),
                        origem: 'cliente',
                        notes: 'Veio no mesmo email da factura.',
                    );

                    $contas['anexos']++;
                    $relatar(sprintf('  #%d %s -> anexo do documento %d', $uid, $pendente['anexo']['nome'], $dono->id));
                } catch (\Throwable $e) {
                    // O ficheiro temporario NAO se apaga: e' a unica copia que
                    // resta, e dizer onde esta e' melhor do que a perder por
                    // arrumacao.
                    Log::warning("faturas:importar-email — anexo {$pendente['anexo']['nome']}: ".$e->getMessage());
                    $relatar(sprintf(
                        '  #%d %s — nao consegui anexar: %s (o ficheiro ficou em storage/app/%s)',
                        $uid,
                        $pendente['anexo']['nome'],
                        $e->getMessage(),
                        $pendente['temp'],
                    ));
                }
            }
        }

        return $contas;
    }

    /**
     * O ficheiro entra sempre primeiro numa pasta temporaria do disco `local`.
     *
     * O extractor precisa de um caminho no disco para correr o pdftotext e o
     * tesseract, e so' depois de o ler e' que se sabe se aquilo e' a factura
     * (vai para as facturas) ou um acompanhante (vai pelo AttachmentService,
     * que decide entre NAS e disco). Escrever primeiro no destino final
     * obrigava a mover ficheiros ja arrumados.
     */
    private function guardarTemporario(array $anexo): string
    {
        $caminho = 'tmp-faturas-email/'.Str::uuid().'.'.$anexo['extensao'];

        Storage::disk('local')->put($caminho, $anexo['conteudo']);

        return $caminho;
    }

    private function moverParaDocumentos(string $temporario, array $anexo, Carbon $recebidoEm, string $hash): string
    {
        $ehPdf = $anexo['extensao'] === 'pdf';

        $pasta = ($ehPdf ? 'accounting-documents' : 'accounting-document-images')
            .'/email/'.$recebidoEm->format('Y/m');

        $nomeFicheiro = Str::limit(Str::slug(pathinfo($anexo['nome'], PATHINFO_FILENAME)), 60, '')
            .'-'.substr($hash, 0, 8).'.'.$anexo['extensao'];

        $destino = $pasta.'/'.$nomeFicheiro;

        Storage::disk('public')->put($destino, Storage::disk('local')->get($temporario));
        Storage::disk('local')->delete($temporario);

        return $destino;
    }

    private function criarDocumento(
        MimeMessage $mensagem,
        array $anexo,
        string $temporario,
        string $hash,
        array $leitura,
        int $totalEmCentimos,
        Carbon $recebidoEm,
    ): AccountingDocument {
        $caminho = $this->moverParaDocumentos($temporario, $anexo, $recebidoEm, $hash);

        $fornecedorLido = trim((string) ($leitura['supplier']['name'] ?? ''));
        $nif = trim((string) ($leitura['supplier']['taxNumber'] ?? ''));
        $factura = $leitura['invoice'] ?? [];

        $documento = new AccountingDocument();

        $documento->fill([
            'tipo' => $this->tipoDeDocumento($factura['type'] ?? null),
            // A finalidade e' uma decisao de quem gere, nao se le da factura.
            'title' => 'outro',
            'estado' => 'pendente',
            'invoice_number' => $factura['number'] ?: null,
            'supplier_nif' => $nif ?: null,
            'atcud' => $factura['atcud'] ?: null,
            'fornecedor' => AccountingDocument::fornecedorPorNif($nif)
                ?: ($fornecedorLido ?: $this->nomeDoRemetente($mensagem)),
            'date' => $this->data($factura['date'] ?? null, $recebidoEm),
            'amount_cents' => $totalEmCentimos,
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

        $this->guardarCaminho($documento, $caminho, $anexo);
        $documento->save();

        return $documento;
    }

    /**
     * Nenhum ficheiro da mensagem tinha total. Cria-se um documento na mesma,
     * por rever, para os ficheiros terem onde viver — deitar fora um email com
     * anexos so' porque o OCR nao os percebeu e' como nao os ter recebido.
     *
     * @param  array{anexo: array, temp: string, leitura: ?array}|null  $principal
     * @param  list<array{anexo: array, temp: string, leitura: ?array}>  $pendentes
     */
    private function criarDocumentoPorRever(
        MimeMessage $mensagem,
        ?array $principal,
        Carbon $recebidoEm,
        array $todosOsAnexos,
        array $pendentes = [],
    ): AccountingDocument {
        $hash = hash('sha256', $todosOsAnexos[0]['conteudo'] ?? ($mensagem->messageId() ?? uniqid()));

        // O texto que o leitor conseguiu tirar de cada ficheiro vai para as
        // Notas. E' a unica pista de porque e' que nao houve total — se o PDF
        // e' uma imagem sem OCR, se o texto saiu mas o padrao do total nao
        // bate certo, ou se falta um binario no servidor.
        $leitura = ['warnings' => ['Nenhum dos ficheiros da mensagem tinha um total legivel.'], 'rawText' => ''];

        foreach ($pendentes as $pendente) {
            if ($pendente['leitura'] === null) {
                continue;
            }

            $leitura['warnings'] = array_values(array_unique(array_merge(
                $leitura['warnings'],
                array_map(
                    fn (string $aviso) => $pendente['anexo']['nome'].': '.$aviso,
                    $pendente['leitura']['warnings'] ?? [],
                ),
            )));

            if (($pendente['leitura']['rawText'] ?? '') !== '') {
                $leitura['rawText'] .= ($leitura['rawText'] !== '' ? "\n\n--- {$pendente['anexo']['nome']} ---\n\n" : "--- {$pendente['anexo']['nome']} ---\n\n")
                    .$pendente['leitura']['rawText'];
            }
        }

        $documento = new AccountingDocument();

        $documento->fill([
            'tipo' => 'fatura',
            'title' => 'outro',
            'estado' => 'por_rever',
            'fornecedor' => $this->nomeDoRemetente($mensagem),
            'date' => $recebidoEm->toDateString(),
            'amount_cents' => 0,
            'iva_cents' => 0,
            'currency' => 'EUR',
            'category' => 'fornecedores',
            'brand_id' => $this->marcaPorDefeito(),
            'notes' => $this->notas($mensagem, $leitura),
            'origem' => 'email',
            'importado_contabilidade' => false,
            'email_message_id' => $mensagem->messageId(),
            'email_de' => Str::limit($mensagem->de(), 250, ''),
            'email_assunto' => Str::limit($mensagem->assunto(), 250, ''),
            'email_recebido_em' => $recebidoEm,
            'ficheiro_hash' => $hash,
        ]);

        if ($principal !== null) {
            $caminho = $this->moverParaDocumentos($principal['temp'], $principal['anexo'], $recebidoEm, $hash);
            $this->guardarCaminho($documento, $caminho, $principal['anexo']);
        }

        $documento->save();

        return $documento;
    }

    /** O PDF vai para `file_path`; uma foto vai para `image_paths`. */
    private function guardarCaminho(AccountingDocument $documento, string $caminho, array $anexo): void
    {
        if ($anexo['extensao'] === 'pdf') {
            $documento->file_path = $caminho;
            $documento->file_name = $anexo['nome'];

            return;
        }

        $documento->image_paths = [$caminho];
        $documento->image_names = [$anexo['nome']];
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
            return $this->extractor->extract(Storage::disk('local')->path($caminhoRelativo));
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

    /**
     * O nome de quem enviou, so' quando serve mesmo de fornecedor provisorio.
     * Caixas automaticas — noreply, no-reply, mailer, faturacao@ do proprio
     * fornecedor de email — nao sao nomes de empresa e nao valem mais do que o
     * campo em branco.
     */
    private function nomeDoRemetente(MimeMessage $mensagem): ?string
    {
        $nome = $mensagem->nomeDe();

        if ($nome === null) {
            return null;
        }

        $automatica = '/\b(no-?reply|nao-?responder|naoresponder|mailer|postmaster|automat|donotreply)\b/i';

        return preg_match($automatica, $nome) ? null : $nome;
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
