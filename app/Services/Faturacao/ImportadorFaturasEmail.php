<?php

namespace App\Services\Faturacao;

use App\Models\AccountingDocument;
use App\Models\Setting;
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
 * Desde 17/09/2026 a caixa e' uma fila: depois de TODOS os ficheiros de uma
 * mensagem estarem guardados no painel, a mensagem vai para o lixo do email
 * (FATURAS_EMAIL_AFTER_IMPORT: lixo | apagar | mover | manter). O que fica na
 * entrada e' o que ainda nao entrou, ou o que nao traz factura.
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
     * @return array{mensagens: int, documentos: int, porRever: int, anexos: int, duplicados: int, semAnexo: int, apagadas: int, descartadas: int, semPdf: int, erros: list<string>}
     */
    public function correr(
        ?int $dias = null,
        ?int $limite = null,
        bool $incluirLidas = false,
        ?callable $relatar = null,
    ): array {
        $config = $this->config();
        $ecra = $relatar ?? static fn (string $linha) => null;

        // Tudo o que a corrida diz fica tambem num ficheiro: a do agendador
        // nao tem ecra, e "porque e' que esta factura nao entrou?" pergunta-se
        // dias depois.
        $relatar = function (string $linha) use ($ecra): void {
            $ecra($linha);
            $this->registar($linha);
        };

        $contas = [
            'mensagens' => 0,
            'documentos' => 0,
            'porRever' => 0,
            'anexos' => 0,
            'duplicados' => 0,
            'semAnexo' => 0,
            'apagadas' => 0,
            'descartadas' => 0,
            'semPdf' => 0,
            'erros' => [],
        ];

        $caixa = new ImapMailbox($config);
        $caixa->ligar();

        try {
            // 17/09/2026: a caixa faturacao@ so' serve para isto, por isso e'
            // uma fila. Le-se a caixa TODA (entrada e spam, qualquer data), e
            // cada mensagem sai dela depois de tratada:
            //  - com factura -> guardada no painel e a mensagem vai para o lixo;
            //  - sem factura -> vai para o lixo (fica registada no log);
            //  - fala de factura mas nao traz PDF (link para a area de
            //    cliente) -> vai para a pasta "Faturas sem PDF", para alguem a
            //    ir buscar a mao.
            // O que fica na entrada e' so' o que deu erro.
            $pastas = $this->pastasAVer($caixa, $config);
            $depois = $this->oQueFazerDepois($caixa, $config, $relatar);
            $desde = $this->desde($dias, $config);
            $restante = max(1, $limite ?? (int) $config['max_messages']);

            $relatar(sprintf(
                '--- %s · desde %s · pastas: %s · depois de importar: %s.',
                Carbon::now()->format('d/m/Y H:i'),
                $desde?->format('d/m/Y') ?? 'sempre (caixa toda)',
                implode(', ', array_map(fn (array $p) => $p['nome'].($p['spam'] ? ' (spam)' : ''), $pastas)),
                $depois['descricao'],
            ));

            foreach ($pastas as $pasta) {
                if ($restante <= 0) {
                    break;
                }

                $restante -= $this->correrPasta($caixa, $config, $pasta, $desde, $restante, $incluirLidas, $depois, $contas, $relatar);
            }
        } finally {
            $caixa->fechar();
            $this->pastaEhSpam = false;
        }

        return $contas;
    }

    /** Enquanto se le uma pasta de spam, o que nascer fica por rever. */
    private bool $pastaEhSpam = false;

    /**
     * @param  array{nome: string, spam: bool}  $pasta
     * @param  array{modo: string, pasta: ?string, descricao: string}  $depois
     * @return int quantas mensagens foram analisadas (contam para o limite)
     */
    private function correrPasta(
        ImapMailbox $caixa,
        array $config,
        array $pasta,
        ?Carbon $desde,
        int $maximo,
        bool $incluirLidas,
        array $depois,
        array &$contas,
        callable $relatar,
    ): int {
        try {
            $caixa->escolherPasta($pasta['nome']);
        } catch (\Throwable $e) {
            $contas['erros'][] = "Pasta {$pasta['nome']}: ".$e->getMessage();
            $relatar("  ERRO a abrir a pasta {$pasta['nome']}: ".$e->getMessage());

            return 0;
        }

        $this->pastaEhSpam = $pasta['spam'];

        // Procura-se tudo desde a data, lido ou nao: a caixa e' lida por
        // pessoas, e um email aberto no telemovel nao pode ficar invisivel.
        // Quem decide o que ja foi tratado e' a lista de UIDs vistos (as
        // mensagens sem anexo, que ficam na caixa) — as importadas saem da
        // caixa e deixam de aparecer. `--todas` ignora a lista.
        $encontrados = $desde !== null
            ? $caixa->procurarDesde($desde, incluirLidas: true)
            : $caixa->procurar('ALL');

        $chaveVistas = $this->chaveVistas($config, $caixa->uidValidity(), $pasta['nome']);
        $vistas = $this->uidsVistos($caixa, $chaveVistas, $config, $pasta['nome']);
        $vistas = array_intersect_key($vistas, array_flip($encontrados));

        $uids = $incluirLidas
            ? $encontrados
            : array_values(array_filter($encontrados, fn (int $uid) => ! isset($vistas[$uid])));

        $saltadas = count($encontrados) - count($uids);
        $restam = max(0, count($uids) - $maximo);
        $uids = array_slice($uids, 0, $maximo);

        $relatar(sprintf(
            '[%s] %d mensagem(ns) · %d ja vistas sem factura · %d a analisar agora%s.',
            $pasta['nome'],
            count($encontrados),
            $saltadas,
            count($uids),
            $restam > 0 ? " · {$restam} ficam para a proxima corrida" : '',
        ));

        try {
            foreach ($uids as $uid) {
                $contas['mensagens']++;

                try {
                    $mensagem = MimeMessage::deBruto($caixa->mensagemEmBruto($uid));

                    $anexos = $mensagem->anexosDeFatura(
                        minimoImagemBytes: max(0, (int) $config['min_image_kb']) * 1024,
                        maximoBytes: max(1, (int) $config['max_attachment_mb']) * 1024 * 1024,
                    );

                    if ($anexos === []) {
                        $contas['semAnexo']++;

                        if (! $this->tirarSemFactura($caixa, $config, $uid, $pasta['nome'], $mensagem, $depois, $contas, $relatar)) {
                            $vistas[$uid] = true;
                            $this->guardarVistos($chaveVistas, $vistas);
                        }

                        continue;
                    }

                    $resultado = $this->processarMensagem($mensagem, $anexos, $uid, $relatar);

                    if ($resultado['naoEFactura']) {
                        if (! $this->tirarSemFactura($caixa, $config, $uid, $pasta['nome'], $mensagem, $depois, $contas, $relatar)) {
                            $vistas[$uid] = true;
                            $this->guardarVistos($chaveVistas, $vistas);
                        }

                        continue;
                    }

                    $contas['documentos'] += $resultado['documentos'];
                    $contas['porRever'] += $resultado['porRever'];
                    $contas['duplicados'] += $resultado['duplicados'];
                    $contas['anexos'] += $resultado['anexos'];

                    if (! $resultado['guardada']) {
                        // Algum ficheiro nao ficou no painel: a mensagem fica
                        // onde esta e volta a ser tentada na proxima corrida
                        // (o que ja entrou nao se duplica — trava o hash).
                        $erro = sprintf('Mensagem #%d (%s): nem todos os ficheiros ficaram no painel — fica na caixa.', $uid, Str::limit($mensagem->assunto(), 40));
                        $contas['erros'][] = $erro;
                        $relatar('  AVISO '.$erro);

                        continue;
                    }

                    // Guardado logo, mensagem a mensagem: o botao do painel
                    // corre dentro do pedido web, e se o PHP o cortar a meio
                    // o `finally` nao chega a correr.
                    $vistas[$uid] = true;
                    $this->guardarVistos($chaveVistas, $vistas);

                    if ($this->arrumar($caixa, $uid, $pasta['nome'], $depois, $relatar)) {
                        $contas['apagadas']++;
                        unset($vistas[$uid]);
                    }
                } catch (\Throwable $e) {
                    $erro = sprintf('Mensagem #%d em %s: %s', $uid, $pasta['nome'], $e->getMessage());
                    $contas['erros'][] = $erro;
                    $relatar('  ERRO '.$erro);
                    Log::warning('faturas:importar-email — '.$erro, ['excepcao' => $e]);
                }
            }
        } finally {
            $this->guardarVistos($chaveVistas, $vistas);
        }

        return count($uids);
    }

    /**
     * Uma mensagem sem factura sai da caixa. Se fala de factura (o
     * fornecedor manda so' um link), vai para a pasta "Faturas sem PDF" em
     * vez do lixo: e' uma factura que alguem tem de ir buscar a mao.
     *
     * @param  array{modo: string, pasta: ?string, descricao: string}  $depois
     * @return bool se a mensagem saiu da pasta
     */
    private function tirarSemFactura(
        ImapMailbox $caixa,
        array $config,
        int $uid,
        string $pastaActual,
        MimeMessage $mensagem,
        array $depois,
        array &$contas,
        callable $relatar,
    ): bool {
        $assunto = Str::limit($mensagem->assunto(), 60);
        $de = Str::limit($mensagem->de(), 40);

        if (strtolower((string) ($config['without_invoice'] ?? 'arrumar')) === 'manter') {
            $relatar(sprintf('  #%d "%s" (%s) — sem factura, fica na caixa.', $uid, $assunto, $de));

            return false;
        }

        if (self::falaDeFactura($mensagem->assunto().' '.mb_substr($mensagem->texto(), 0, 5000))) {
            $pastaSemPdf = $this->pastaSemPdf($caixa, $config);

            if ($pastaSemPdf !== null && $pastaSemPdf !== $pastaActual) {
                $this->arrumar($caixa, $uid, $pastaActual, ['modo' => 'mover', 'pasta' => $pastaSemPdf, 'descricao' => ''], static fn () => null);
                $contas['semPdf']++;
                $relatar(sprintf('  #%d "%s" (%s) — fala de factura mas nao traz PDF -> %s', $uid, $assunto, $de, $pastaSemPdf));

                return true;
            }
        }

        if ($this->arrumar($caixa, $uid, $pastaActual, $depois, static fn () => null)) {
            $contas['descartadas']++;
            $relatar(sprintf('  #%d "%s" (%s) — sem factura -> %s', $uid, $assunto, $de, $depois['pasta'] ?? 'apagada'));

            return true;
        }

        $relatar(sprintf('  #%d "%s" (%s) — sem factura, fica na caixa.', $uid, $assunto, $de));

        return false;
    }

    private ?string $cachePastaSemPdf = null;

    /** A pasta "Faturas sem PDF", criada ao lado da entrada se nao existir. */
    private function pastaSemPdf(ImapMailbox $caixa, array $config): ?string
    {
        if ($this->cachePastaSemPdf !== null) {
            return $this->cachePastaSemPdf;
        }

        $nome = trim((string) ($config['no_pdf_folder'] ?? 'Faturas sem PDF'));

        if ($nome === '') {
            return null;
        }

        $this->cachePastas ??= $caixa->pastasComAtributos();
        $existentes = array_map(fn (array $p) => $p['nome'], $this->cachePastas);

        // Servidores como o Dovecot do Plesk poem tudo debaixo de "INBOX.".
        $prefixo = '';

        foreach ($this->cachePastas as $pasta) {
            if (preg_match('/^INBOX([.\/])./i', $pasta['nome'], $m)) {
                $prefixo = 'INBOX'.$m[1];

                break;
            }
        }

        foreach ([$nome, $prefixo.$nome] as $candidato) {
            if (in_array($candidato, $existentes, true)) {
                return $this->cachePastaSemPdf = $candidato;
            }
        }

        $novo = str_starts_with(strtoupper($nome), 'INBOX') ? $nome : $prefixo.$nome;
        $caixa->criarPasta($novo);
        $this->cachePastas = null;

        return $this->cachePastaSemPdf = $novo;
    }

    private function registar(string $linha): void
    {
        try {
            $ficheiro = storage_path('logs/faturas-email.log');
            @file_put_contents($ficheiro, $linha."\n", FILE_APPEND | LOCK_EX);
        } catch (\Throwable) {
            // O registo nunca pode parar a importacao.
        }
    }

    /**
     * A factura ja esta no painel — agora a mensagem sai da caixa.
     *
     * So' se chega aqui depois de TODOS os ficheiros da mensagem estarem
     * guardados (como documento ou como anexo dele). O PDF fica no painel e
     * no NAS; o email vai para o lixo, de onde ainda se recupera.
     *
     * @param  array{modo: string, pasta: ?string, descricao: string}  $depois
     * @return bool se a mensagem saiu da pasta
     */
    private function arrumar(ImapMailbox $caixa, int $uid, string $pastaActual, array $depois, callable $relatar): bool
    {
        switch ($depois['modo']) {
            case 'apagar':
                $caixa->apagar($uid);
                $relatar("  #{$uid} apagada da caixa.");

                return true;

            case 'lixo':
            case 'mover':
                if ($depois['pasta'] === null || $depois['pasta'] === $pastaActual) {
                    $caixa->marcarLida($uid);

                    return false;
                }

                $caixa->marcarLida($uid);
                $caixa->mover($uid, $depois['pasta']);
                $relatar("  #{$uid} -> {$depois['pasta']}");

                return true;

            default: // manter
                $caixa->marcarLida($uid);

                return false;
        }
    }

    /**
     * @return array{modo: string, pasta: ?string, descricao: string}
     */
    private function oQueFazerDepois(ImapMailbox $caixa, array $config, callable $relatar): array
    {
        $modo = strtolower(trim((string) ($config['after_import'] ?? 'lixo')));

        if ($modo === 'apagar') {
            return ['modo' => 'apagar', 'pasta' => null, 'descricao' => 'apagar de vez'];
        }

        if ($modo === 'mover') {
            $destino = trim((string) ($config['processed_folder'] ?? ''));

            if ($destino === '') {
                $relatar('AVISO: FATURAS_EMAIL_AFTER_IMPORT=mover mas falta FATURAS_EMAIL_PROCESSED_FOLDER — as mensagens ficam na caixa.');

                return ['modo' => 'manter', 'pasta' => null, 'descricao' => 'deixar na caixa (marcada como lida)'];
            }

            $caixa->criarPasta($destino);

            return ['modo' => 'mover', 'pasta' => $destino, 'descricao' => "mover para {$destino}"];
        }

        if ($modo === 'manter') {
            return ['modo' => 'manter', 'pasta' => null, 'descricao' => 'deixar na caixa (marcada como lida)'];
        }

        $lixo = trim((string) ($config['trash_folder'] ?? '')) ?: $this->pastaEspecial($caixa, 'lixo');

        if ($lixo === null) {
            // Sem lixo conhecido nao se apaga de vez sem ninguem ter pedido:
            // e' preferivel uma caixa cheia a uma factura perdida.
            $relatar('AVISO: nao encontrei a pasta do lixo. Define FATURAS_EMAIL_TRASH_FOLDER (ou FATURAS_EMAIL_AFTER_IMPORT=apagar). Por agora as mensagens ficam na caixa.');

            return ['modo' => 'manter', 'pasta' => null, 'descricao' => 'deixar na caixa (sem pasta do lixo)'];
        }

        return ['modo' => 'lixo', 'pasta' => $lixo, 'descricao' => "mover para o lixo ({$lixo})"];
    }

    /**
     * A entrada, mais o spam (a nao ser que se desligue). Uma factura que o
     * servidor ache suspeita vai parar ao spam e ninguem a ve la'.
     *
     * @return list<array{nome: string, spam: bool}>
     */
    private function pastasAVer(ImapMailbox $caixa, array $config): array
    {
        $nomes = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) ($config['folders'] ?? '') ?: (string) $config['folder']),
        )));

        $pastas = array_map(fn (string $n) => ['nome' => $n, 'spam' => false], $nomes ?: ['INBOX']);

        if ((bool) ($config['include_junk'] ?? true)) {
            $spam = $this->pastaEspecial($caixa, 'spam');

            if ($spam !== null && ! in_array($spam, $nomes, true)) {
                $pastas[] = ['nome' => $spam, 'spam' => true];
            }
        }

        return $pastas;
    }

    /** @var list<array{nome: string, atributos: list<string>}>|null */
    private ?array $cachePastas = null;

    /** O nome da pasta do lixo ou do spam, pelos atributos ou, na falta deles, pelo nome. */
    private function pastaEspecial(ImapMailbox $caixa, string $qual): ?string
    {
        $this->cachePastas ??= $caixa->pastasComAtributos();

        [$atributo, $padrao] = $qual === 'lixo'
            ? ['\\trash', '/^(inbox[.\/])?(trash|lixo|lixeira|reciclagem|deleted items|deleted messages|itens eliminados|itens exclu.dos)$/i']
            : ['\\junk', '/^(inbox[.\/])?(junk|spam|junk e-?mail|lixo eletr.*|correio (eletr.*)?n.o solicitado)$/i'];

        foreach ($this->cachePastas as $pasta) {
            if (in_array($atributo, $pasta['atributos'], true) && ! in_array('\\noselect', $pasta['atributos'], true)) {
                return $pasta['nome'];
            }
        }

        foreach ($this->cachePastas as $pasta) {
            if (preg_match($padrao, $pasta['nome']) && ! in_array('\\noselect', $pasta['atributos'], true)) {
                return $pasta['nome'];
            }
        }

        return null;
    }

    /**
     * Por omissao a caixa toda (null): e' uma fila, e o que la' esta ainda
     * nao foi tratado, seja de quando for. `--dias` limita.
     */
    private function desde(?int $dias, array $config): ?Carbon
    {
        $dias ??= isset($config['days']) && $config['days'] !== null ? (int) $config['days'] : null;

        return ($dias === null || $dias <= 0) ? null : Carbon::now()->subDays($dias);
    }

    /**
     * So' olha, nao mexe: diz, mensagem a mensagem, o que o importador faria
     * e porque. Com `$texto`, procura em TODAS as pastas (lixo incluido) e em
     * qualquer data — e' a resposta a "onde esta a factura X?".
     *
     * @return list<array{pasta: string, uid: int, data: string, de: string, assunto: string, lida: bool, anexos: list<string>, estado: string}>
     */
    public function listar(?int $dias = null, ?int $limite = null, ?string $texto = null): array
    {
        $config = $this->config();
        $caixa = new ImapMailbox($config);
        $caixa->ligar();

        try {
            $desde = $this->desde($dias, $config);
            $maximo = max(1, $limite ?? 100);

            $pastas = $texto !== null
                ? array_map(
                    fn (array $p) => ['nome' => $p['nome'], 'spam' => in_array('\\junk', $p['atributos'], true), 'lixo' => in_array('\\trash', $p['atributos'], true)],
                    array_filter($caixa->pastasComAtributos(), fn (array $p) => ! in_array('\\noselect', $p['atributos'], true)),
                )
                : $this->pastasAVer($caixa, $config);

            $linhas = [];

            foreach ($pastas as $pasta) {
                try {
                    $caixa->escolherPasta($pasta['nome'], soLeitura: true);
                } catch (\Throwable $e) {
                    continue;
                }

                if ($texto !== null) {
                    $uids = $caixa->procurarTexto($texto);
                    $porLer = array_flip($caixa->procurar('UNSEEN'));
                } else {
                    $uids = $desde !== null ? $caixa->procurarDesde($desde, incluirLidas: true) : $caixa->procurar('ALL');
                    $porLer = array_flip($caixa->procurar('UNSEEN'));
                }

                $vistas = $this->uidsVistos($caixa, $this->chaveVistas($config, $caixa->uidValidity(), $pasta['nome']), $config, $pasta['nome']);

                // As mais recentes primeiro: e' quase sempre por essas que se pergunta.
                foreach (array_slice(array_reverse($uids), 0, $maximo) as $uid) {
                    $linha = ['pasta' => $pasta['nome']] + $this->avaliar($caixa, $uid, $config, isset($vistas[$uid]), ! isset($porLer[$uid]), $desde, $texto !== null);

                    if (! empty($pasta['lixo'])) {
                        $linha['estado'] = str_starts_with($linha['estado'], 'ja esta no painel')
                            ? 'no LIXO — ja esta no painel (tudo certo)'
                            : 'no LIXO — '.$linha['estado'].' (o importador nao le o lixo)';
                    }

                    $linhas[] = $linha;
                }
            }

            return $linhas;
        } finally {
            $caixa->fechar();
        }
    }

    /** @return array{uid: int, data: string, de: string, assunto: string, lida: bool, anexos: list<string>, estado: string} */
    private function avaliar(ImapMailbox $caixa, int $uid, array $config, bool $vista, bool $lida, ?Carbon $desde, bool $foraDaJanelaConta): array
    {
        try {
            $mensagem = MimeMessage::deBruto($caixa->mensagemEmBruto($uid));
        } catch (\Throwable $e) {
            return ['uid' => $uid, 'data' => '?', 'de' => '?', 'assunto' => '?', 'lida' => $lida, 'anexos' => [], 'estado' => 'ERRO a ler: '.$e->getMessage()];
        }

        // Todas as partes, para se ver tambem o que foi posto de lado e porque.
        $partes = array_values(array_filter(
            $mensagem->todasAsPartes(),
            fn (array $p) => $p['nome'] !== null || ! str_starts_with($p['mime'], 'text/'),
        ));

        $anexos = $mensagem->anexosDeFatura(
            minimoImagemBytes: max(0, (int) $config['min_image_kb']) * 1024,
            maximoBytes: max(1, (int) $config['max_attachment_mb']) * 1024 * 1024,
        );

        $importados = 0;

        foreach ($anexos as $anexo) {
            if ($this->documentoPorHash($anexo['conteudo']) !== null) {
                $importados++;
            }
        }

        $data = $mensagem->data();

        $estado = match (true) {
            $anexos === [] && self::falaDeFactura($mensagem->assunto().' '.mb_substr($mensagem->texto(), 0, 5000)) => 'fala de factura mas NAO traz PDF (link?) -> vai para "Faturas sem PDF"',
            $anexos === [] && $partes === [] => 'sem anexos e sem factura -> vai para o lixo',
            $anexos === [] => 'anexos que nao sao PDF/imagem/dados -> vai para o lixo',
            $importados > 0 && ($vista || $importados === count($anexos)) => 'ja esta no painel — sai da caixa na proxima corrida',
            $importados > 0 => 'em parte importada — o resto entra na proxima corrida',
            $foraDaJanelaConta && $desde !== null && $data !== null && $data < $desde => 'POR IMPORTAR, mas fora da janela ('.$desde->format('d/m/Y').') — usa --dias',
            default => 'POR IMPORTAR — entra na proxima corrida (se o PDF nao for factura, vai para o lixo)',
        };

        return [
            'uid' => $uid,
            'data' => $data?->format('d/m/Y H:i') ?? '?',
            'de' => $mensagem->de(),
            'assunto' => $mensagem->assunto(),
            'lida' => $lida,
            'anexos' => array_map(
                fn (array $p) => ($p['nome'] ?? '(sem nome)').' ['.$p['mime'].', '.round(strlen($p['conteudo']) / 1024).' KB]',
                $partes,
            ),
            'estado' => $estado,
        ];
    }
    /**
     * As mensagens SEM factura que ja se viram, por caixa, pasta e
     * UIDVALIDITY. As que tinham factura saem da caixa e nao precisam de
     * lista. (A chave `vistas.` de 17/09 de manha misturava as duas coisas e
     * escondia facturas ja importadas que agora tem de sair da caixa — por
     * isso nao se aproveita.)
     */
    private function chaveVistas(array $config, string $uidValidity, string $pasta): string
    {
        return 'faturas_email.sem_factura.'.md5(strtolower((string) $config['username']).'|'.$pasta.'|'.$uidValidity);
    }

    /** @return array<int, true> */
    private function uidsVistos(ImapMailbox $caixa, string $chave, array $config, string $pasta): array
    {
        $valor = Setting::get($chave);

        // Primeira vez: a lista da 1.a correccao so' tinha mensagens sem
        // anexo da entrada, por isso serve de semente.
        if ($valor === null && $pasta === (string) $config['folder']) {
            $valor = Setting::get('faturas_email.sem_anexo.'.md5(strtolower((string) $config['username']).'|'.$config['folder']));
        }

        $lista = json_decode((string) ($valor ?? '[]'), true);

        return is_array($lista)
            ? array_fill_keys(array_map('intval', $lista), true)
            : [];
    }

    /** @param  array<int, true>  $vistas */
    private function guardarVistos(string $chave, array $vistas): void
    {
        Setting::set($chave, json_encode(array_keys($vistas)));
    }

    /**
     * O documento que ja tem este ficheiro.
     *
     * A API de facturas grava o hash em sha1 e o importador em sha256 — ate
     * 17/09/2026 os dois nunca se encontravam, e uma factura enviada pela API
     * voltava a entrar quando chegava tambem por email. Procura-se pelos dois.
     */
    private function documentoPorHash(string $conteudo): ?AccountingDocument
    {
        return AccountingDocument::query()
            ->whereIn('ficheiro_hash', [hash('sha256', $conteudo), sha1($conteudo)])
            ->first();
    }

    /**
     * A mesma factura ja lancada por outra porta (a mao, pela API, ou com
     * outro PDF).
     *
     * So' com dados de confianca: o ATCUD (unico por factura), ou o numero
     * lido do QR **e** o mesmo total. O numero lido do texto do PDF nao
     * serve — o leitor apanha as vezes o numero de cliente ou de contrato, que
     * e' igual em todas as facturas do fornecedor, e a partir da segunda
     * todas eram dadas como "ja existe" (17/09/2026).
     */
    private function documentoPorNumero(array $leitura, int $totalEmCentimos): ?AccountingDocument
    {
        $nif = trim((string) ($leitura['supplier']['taxNumber'] ?? ''));
        $atcud = trim((string) ($leitura['invoice']['atcud'] ?? ''));

        if ($nif === '') {
            return null;
        }

        if (mb_strlen($atcud) >= 5 && $atcud !== '0') {
            return AccountingDocument::query()
                ->where('atcud', $atcud)
                ->where('supplier_nif', $nif)
                ->first();
        }

        $numero = $this->numeroDoQr($leitura);

        if ($numero === '') {
            return null;
        }

        return AccountingDocument::query()
            ->where('invoice_number', $numero)
            ->where('supplier_nif', $nif)
            ->where('amount_cents', $totalEmCentimos)
            ->first();
    }

    /** O numero da factura, so' quando vem do QR (campo G). */
    private function numeroDoQr(array $leitura): string
    {
        $qr = (string) ($leitura['qrData'] ?? '');

        if ($qr === '' || ! preg_match('/(?:^|\*)G:([^*]+)/', $qr, $m)) {
            return '';
        }

        return trim($m[1]);
    }

    /** @param  list<array{anexo: array, temp: string, leitura: ?array}>  $pendentes */
    private function nenhumPareceFactura(array $pendentes): bool
    {
        foreach ($pendentes as $pendente) {
            $leitura = $pendente['leitura'];

            // Dados (CSV/XML) ou leitura falhada: na duvida, e' factura.
            if (! $pendente['anexo']['legivel'] || $leitura === null) {
                return false;
            }

            $texto = trim((string) ($leitura['rawText'] ?? ''));

            if ($texto === '' || mb_strlen($texto) < 40) {
                return false; // nao se conseguiu ler: nao se decide as cegas
            }

            if (! empty($leitura['qrData'])
                || trim((string) ($leitura['invoice']['atcud'] ?? '')) !== ''
                || (float) ($leitura['invoice']['total'] ?? 0) > 0
                || self::falaDeFactura($texto)) {
                return false;
            }
        }

        return true;
    }

    public static function falaDeFactura(string $texto): bool
    {
        return (bool) preg_match(
            '/\b(fatura|factura|faturas|facturas|fatura-recibo|invoice|recibo|nota\s+de\s+cr[eé]dito|atcud|documento\s+de\s+cobran[cç]a)\b/iu',
            $texto,
        );
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
     * @return array{documentos: int, porRever: int, duplicados: int, anexos: int, guardada: bool, naoEFactura: bool}
     */
    private function processarMensagem(MimeMessage $mensagem, array $anexos, int $uid, callable $relatar): array
    {
        $contas = ['documentos' => 0, 'porRever' => 0, 'duplicados' => 0, 'anexos' => 0, 'guardada' => false, 'naoEFactura' => false];
        $falhas = 0;
        $recebidoEm = $mensagem->data() ? Carbon::instance($mensagem->data()) : Carbon::now();

        /** @var list<AccountingDocument> $documentos */
        $documentos = [];
        /** @var list<array{anexo: array, temp: string}> $porAnexar */
        $porAnexar = [];

        /** @var list<array{anexo: array, temp: string, hash: string, leitura: array, total: int}> $candidatos */
        $candidatos = [];

        // Preenchido quando um dos ficheiros da mensagem ja e' um documento.
        $documentoExistente = null;

        foreach ($anexos as $anexo) {
            $hash = hash('sha256', $anexo['conteudo']);

            $jaImportado = $this->documentoPorHash($anexo['conteudo']);

            if ($jaImportado !== null) {
                $contas['duplicados']++;

                // Guardar QUAL o documento, nao so' que existe. A 09/09/2026 a
                // factura da Via Verde foi salta por ja estar importada, e o
                // Detalhe — que sozinho tem o maior total dos que sobraram —
                // tomou-lhe o lugar e criou um documento novo de 338,45. O
                // "maior total manda" so' e' verdade quando os candidatos estao
                // todos em jogo; se um deles ja e' um documento, e' esse o
                // documento da mensagem e os outros sao anexos dele.
                $documentoExistente ??= $jaImportado;

                $relatar(sprintf(
                    '  #%d %s — ja tinha sido importado (documento %d).',
                    $uid,
                    $anexo['nome'],
                    $jaImportado->id,
                ));

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
                // Sem total nao e' a factura — e' a capa, o aviso, o anexo.
                // A leitura vai junto: se no fim nenhum ficheiro tiver total, e'
                // dela que sai o texto lido para as Notas.
                $porAnexar[] = ['anexo' => $anexo, 'temp' => $temporario, 'leitura' => $leitura];

                continue;
            }

            $candidatos[] = [
                'anexo' => $anexo,
                'temp' => $temporario,
                'hash' => $hash,
                'leitura' => $leitura,
                'total' => $total,
            ];
        }

        // ── Um gasto por mensagem: manda o maior total ──────────────────────
        //
        // O email da Via Verde traz o extracto (596,53) e o Detalhe, que
        // reparte o mesmo valor por viatura e mostra subtotais — o maior deles
        // 338,45. Os dois tem total, e com "cada total e' uma factura" o
        // contabilista lancava 596,53 + 338,45 todos os meses. Por isso o
        // maior e' o documento e os outros ficam como anexos dele.
        //
        // EXCEPCAO (17/09/2026): ficheiros com QR proprio e numeros de
        // factura diferentes sao facturas diferentes, e cada uma da' o seu
        // documento. Antes, um email com duas facturas deixava uma delas
        // escondida como anexo da outra — contava como "saltada".
        usort($candidatos, fn (array $a, array $b) => $b['total'] <=> $a['total']);

        $separadas = [];
        $numerosVistos = [];

        foreach ($candidatos as $indice => $candidato) {
            $numero = $this->numeroDoQr($candidato['leitura']);

            if ($numero !== '' && ! isset($numerosVistos[$numero])) {
                $numerosVistos[$numero] = true;
                $separadas[$indice] = $candidato;
            }
        }

        if (count($separadas) < 2) {
            $separadas = [];
        }

        if ($documentoExistente !== null && $separadas !== []) {
            // A que ja existe (pelo hash) nao volta a nascer.
            $separadas = array_filter(
                $separadas,
                fn (array $c) => $this->numeroDoQr($c['leitura']) !== (string) $documentoExistente->invoice_number,
            );
        }

        $resto = array_values(array_diff_key($candidatos, $separadas));

        if ($separadas !== []) {
            $principais = array_values($separadas);
        } elseif ($documentoExistente === null && $resto !== []) {
            $principais = [array_shift($resto)];
        } else {
            $principais = [];
        }

        // A factura desta mensagem ja esta no painel: e' esse o documento, e o
        // que nao for factura propria passa a ser anexo dele.
        if ($documentoExistente !== null) {
            $documentos[] = $documentoExistente;

            if ($resto !== []) {
                $relatar(sprintf(
                    '  #%d os restantes ficheiros vao para o documento %d, que ja existia.',
                    $uid,
                    $documentoExistente->id,
                ));
            }
        }

        foreach ($principais as $principal) {
            // O ficheiro e' novo, mas a factura pode nao ser: uma que alguem
            // lancou a mao ou pela API, com outro PDF, nao nasce outra vez.
            $mesmaFactura = $this->documentoPorNumero($principal['leitura'], $principal['total']);

            if ($mesmaFactura !== null) {
                $contas['duplicados']++;

                $relatar(sprintf(
                    '  #%d %s — a factura %s ja existe no painel (documento %d).',
                    $uid,
                    $principal['anexo']['nome'],
                    $mesmaFactura->invoice_number,
                    $mesmaFactura->id,
                ));

                // O PDF so' se guarda se o documento nao tiver ficheiro nenhum.
                if (blank($mesmaFactura->file_path) && empty($mesmaFactura->image_paths)) {
                    $caminho = $this->moverParaDocumentos($principal['temp'], $principal['anexo'], $recebidoEm, $principal['hash']);
                    $this->guardarCaminho($mesmaFactura, $caminho, $principal['anexo']);
                    $mesmaFactura->ficheiro_hash ??= $principal['hash'];
                    $mesmaFactura->save();
                    $relatar(sprintf('  #%d %s -> ficheiro do documento %d, que nao tinha nenhum', $uid, $principal['anexo']['nome'], $mesmaFactura->id));
                } else {
                    Storage::disk('local')->delete($principal['temp']);
                }

                if (! in_array($mesmaFactura->id, array_map(fn ($d) => $d->id, $documentos), true)) {
                    $documentos[] = $mesmaFactura;
                }

                continue;
            }

            $outros = $separadas === []
                ? array_map(fn (array $c) => ['nome' => $c['anexo']['nome'], 'total' => $c['total'] / 100], $resto)
                : [];

            $documento = $this->criarDocumento(
                $mensagem,
                $principal['anexo'],
                $principal['temp'],
                $principal['hash'],
                $principal['leitura'],
                $principal['total'],
                $recebidoEm,
                $outros,
            );

            $documentos[] = $documento;
            $contas['documentos']++;

            if ($documento->estado === 'por_rever') {
                $contas['porRever']++;
            }

            $relatar(sprintf(
                '  #%d %s -> documento %d (%s, %s)%s',
                $uid,
                $principal['anexo']['nome'],
                $documento->id,
                $documento->fornecedor ?: 'fornecedor por identificar',
                number_format($documento->amount, 2, ',', '.').' EUR',
                $outros !== [] ? '  ('.count($outros).' outro(s) ficheiro(s) com total, anexados)' : '',
            ));
        }

        foreach ($resto as $candidato) {
            $porAnexar[] = [
                'anexo' => $candidato['anexo'],
                'temp' => $candidato['temp'],
                'leitura' => $candidato['leitura'],
            ];
        }

        // Nenhum documento e so' ficheiros lidos que claramente NAO sao
        // facturas (texto legivel, sem QR, sem ATCUD, sem total, sem a
        // palavra factura/recibo): nao se cria nada, e a mensagem sai da
        // caixa como qualquer outro email sem factura. Na duvida — um PDF
        // digitalizado que o OCR nao leu, um XML, um CSV — fica por rever.
        if ($documentos === [] && $porAnexar !== [] && $this->nenhumPareceFactura($porAnexar)) {
            foreach ($porAnexar as $pendente) {
                Storage::disk('local')->delete($pendente['temp']);
            }

            $contas['naoEFactura'] = true;
            $relatar(sprintf(
                '  #%d "%s" — os PDF foram lidos e nenhum e\' uma factura (%s).',
                $uid,
                Str::limit($mensagem->assunto(), 40),
                implode(', ', array_map(fn (array $p) => $p['anexo']['nome'], $porAnexar)),
            ));

            return $contas;
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
                // Reprocessar a mesma mensagem nao pode encher o documento de
                // copias do mesmo anexo.
                $jaAnexado = $dono->anexos()
                    ->where('original_name', $pendente['anexo']['nome'])
                    ->where('file_size', strlen($pendente['anexo']['conteudo']))
                    ->exists();

                if ($jaAnexado) {
                    Storage::disk('local')->delete($pendente['temp']);
                    $relatar(sprintf('  #%d %s — ja estava anexado.', $uid, $pendente['anexo']['nome']));

                    continue;
                }

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
                    $falhas++;
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

        // So' se da' a mensagem por arrumada quando tudo o que trazia esta no
        // painel: o email e' apagado a seguir, e um ficheiro que falhou nao
        // pode desaparecer com ele.
        $contas['guardada'] = $documentos !== [] && $falhas === 0;

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
        array $outrosTotais = [],
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
            // Do spam nunca vai direto ao contabilista: uma "factura" em PDF e'
            // o isco mais comum de phishing.
            'estado' => $this->pastaEhSpam ? 'por_rever' : 'pendente',
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
            'notes' => $this->notas($mensagem, $leitura, $outrosTotais),
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

    private function notas(MimeMessage $mensagem, array $leitura, array $outrosTotais = []): string
    {
        $blocos = [];

        if ($this->pastaEhSpam) {
            $blocos[] = 'ATENCAO: este email estava na pasta de SPAM. Confirma que o fornecedor e a factura sao verdadeiros antes de a aprovar.';
        }

        // Quem confere tem de saber que havia mais ficheiros com valor, e
        // quais. Se um deles for mesmo uma segunda factura, e' aqui que se ve.
        if ($outrosTotais !== []) {
            $linhas = array_map(
                fn (array $o) => sprintf('- %s: %s EUR', $o['nome'], number_format($o['total'], 2, ',', '.')),
                $outrosTotais,
            );

            $blocos[] = "ATENCAO: outros ficheiros desta mensagem tambem tinham total e ficaram como anexos.\n"
                ."Se algum for uma factura a parte, tem de ser lancado a mao:\n".implode("\n", $linhas);
        }

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
