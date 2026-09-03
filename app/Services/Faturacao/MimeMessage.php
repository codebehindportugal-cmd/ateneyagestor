<?php

namespace App\Services\Faturacao;

use DateTimeImmutable;

/**
 * Desmonta uma mensagem de email em bruto: cabecalhos e anexos.
 *
 * Trabalha sobre os bytes originais e nunca sobre linhas rejuntadas. Um PDF
 * que venha em 8bit dentro de um multipart tem \r\n la' dentro que fazem parte
 * do ficheiro: partir por linhas e voltar a juntar estragava-o em silencio, e o
 * PDF so' aparecia corrompido mais tarde, na mao do contabilista.
 */
class MimeMessage
{
    /** @var array<string, string> */
    private array $cabecalhos = [];

    /** @var list<array{mime: string, nome: ?string, disposicao: string, contentId: ?string, conteudo: string}> */
    private array $partes = [];

    private function __construct()
    {
    }

    public static function deBruto(string $bruto): self
    {
        $mensagem = new self();

        [$cabecalhosCrus, $corpo] = self::separarCabecalhos($bruto);

        $mensagem->cabecalhos = self::lerCabecalhos($cabecalhosCrus);
        $mensagem->partes = self::lerParte($mensagem->cabecalhos, $corpo);

        return $mensagem;
    }

    // ── Cabecalhos ───────────────────────────────────────────────────────────

    public function cabecalho(string $nome): ?string
    {
        return $this->cabecalhos[strtolower($nome)] ?? null;
    }

    public function assunto(): string
    {
        return self::descodificarCabecalho($this->cabecalho('subject') ?? '');
    }

    public function de(): string
    {
        $valor = self::descodificarCabecalho($this->cabecalho('from') ?? '');

        return trim($valor);
    }

    public function enderecoDe(): ?string
    {
        if (preg_match('/<([^>]+)>/', $this->cabecalho('from') ?? '', $m)) {
            return trim($m[1]);
        }

        $valor = trim($this->cabecalho('from') ?? '');

        return filter_var($valor, FILTER_VALIDATE_EMAIL) ? $valor : null;
    }

    /** O nome de quem envia, quando existe — serve de fornecedor provisorio. */
    public function nomeDe(): ?string
    {
        $valor = self::descodificarCabecalho($this->cabecalho('from') ?? '');
        $valor = trim(preg_replace('/<[^>]*>/', '', $valor) ?? '');
        $valor = trim($valor, " \t\"'");

        return $valor !== '' ? $valor : null;
    }

    public function messageId(): ?string
    {
        $valor = trim($this->cabecalho('message-id') ?? '');

        return $valor !== '' ? mb_substr($valor, 0, 255) : null;
    }

    public function data(): ?DateTimeImmutable
    {
        $valor = trim($this->cabecalho('date') ?? '');

        if ($valor === '') {
            return null;
        }

        // "Tue, 02 Sep 2026 14:31:07 +0100" — mas 2 de Setembro de 2026 foi uma
        // quarta-feira. Ha servidores de email que poem o dia da semana errado,
        // e o PHP, em vez de o ignorar, salta para a proxima terca: a factura
        // ficava com data de uma semana depois. O nome do dia nao acrescenta
        // nada ao que ja la esta, por isso vai fora antes de ler.
        $valor = preg_replace('/^[A-Za-z]{3,9},\s*/', '', $valor) ?? $valor;

        // Zonas em texto no fim — "(CEST)", "(GMT+01:00)" — tambem confundem.
        $valor = trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $valor) ?? $valor);

        try {
            return new DateTimeImmutable($valor);
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Anexos ───────────────────────────────────────────────────────────────

    /** @return list<array{mime: string, nome: ?string, disposicao: string, contentId: ?string, conteudo: string}> */
    public function todasAsPartes(): array
    {
        return $this->partes;
    }

    /**
     * So' o que pode ser uma factura: PDF sempre; imagens apenas quando vem
     * como anexo verdadeiro e com tamanho de fotografia. Sem este travao, o
     * logotipo da assinatura de cada fornecedor entrava como documento de
     * contabilidade — e sao dezenas por semana.
     *
     * @return list<array{nome: string, mime: string, conteudo: string, extensao: string}>
     */
    public function anexosDeFatura(int $minimoImagemBytes = 30720, int $maximoBytes = 20971520): array
    {
        $anexos = [];

        foreach ($this->partes as $parte) {
            $mime = strtolower($parte['mime']);
            $tamanho = strlen($parte['conteudo']);
            $extensao = self::extensaoDe($parte['nome'], $mime);

            if ($tamanho === 0 || $tamanho > $maximoBytes) {
                continue;
            }

            $ehPdf = $mime === 'application/pdf'
                || $mime === 'application/x-pdf'
                || $extensao === 'pdf';

            $ehImagem = str_starts_with($mime, 'image/')
                || in_array($extensao, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'tif', 'tiff'], true);

            if (! $ehPdf && ! $ehImagem) {
                continue;
            }

            if ($ehImagem && ! $ehPdf) {
                // Imagem embutida no corpo (logotipo, botao, separador).
                if ($parte['contentId'] !== null && $parte['disposicao'] !== 'attachment') {
                    continue;
                }

                if ($tamanho < $minimoImagemBytes) {
                    continue;
                }
            }

            $anexos[] = [
                'nome' => $parte['nome'] ?: ('anexo.'.($extensao ?: 'bin')),
                'mime' => $mime,
                'conteudo' => $parte['conteudo'],
                'extensao' => $extensao ?: ($ehPdf ? 'pdf' : 'jpg'),
            ];
        }

        return $anexos;
    }

    // ── Leitura ──────────────────────────────────────────────────────────────

    /** @return array{0: string, 1: string} */
    private static function separarCabecalhos(string $bruto): array
    {
        $corte = strpos($bruto, "\r\n\r\n");
        $salto = 4;

        if ($corte === false) {
            $corte = strpos($bruto, "\n\n");
            $salto = 2;
        }

        if ($corte === false) {
            return [$bruto, ''];
        }

        return [substr($bruto, 0, $corte), substr($bruto, $corte + $salto)];
    }

    /** @return array<string, string> */
    private static function lerCabecalhos(string $cru): array
    {
        // Desdobrar: uma continuacao comeca por espaco ou tabulacao.
        $cru = preg_replace('/\r?\n[ \t]+/', ' ', $cru) ?? $cru;

        $cabecalhos = [];

        foreach (preg_split('/\r?\n/', $cru) ?: [] as $linha) {
            if (! str_contains($linha, ':')) {
                continue;
            }

            [$nome, $valor] = explode(':', $linha, 2);
            $nome = strtolower(trim($nome));

            if ($nome === '' || isset($cabecalhos[$nome])) {
                continue;
            }

            $cabecalhos[$nome] = trim($valor);
        }

        return $cabecalhos;
    }

    /**
     * @param  array<string, string>  $cabecalhos
     * @return list<array{mime: string, nome: ?string, disposicao: string, contentId: ?string, conteudo: string}>
     */
    private static function lerParte(array $cabecalhos, string $corpo): array
    {
        $tipo = $cabecalhos['content-type'] ?? 'text/plain';
        [$mime, $parametros] = self::lerTipoDeConteudo($tipo);

        if (str_starts_with($mime, 'multipart/') && ! empty($parametros['boundary'])) {
            $resultado = [];

            foreach (self::dividirPorFronteira($corpo, $parametros['boundary']) as $segmento) {
                [$subCabecalhosCrus, $subCorpo] = self::separarCabecalhos($segmento);
                $subCabecalhos = self::lerCabecalhos($subCabecalhosCrus);

                foreach (self::lerParte($subCabecalhos, $subCorpo) as $sub) {
                    $resultado[] = $sub;
                }
            }

            return $resultado;
        }

        if ($mime === 'message/rfc822') {
            [$subCabecalhosCrus, $subCorpo] = self::separarCabecalhos($corpo);

            return self::lerParte(self::lerCabecalhos($subCabecalhosCrus), $subCorpo);
        }

        $disposicaoCrua = $cabecalhos['content-disposition'] ?? '';
        [$disposicao, $parametrosDisposicao] = self::lerTipoDeConteudo($disposicaoCrua);

        $nome = $parametrosDisposicao['filename'] ?? $parametros['name'] ?? null;

        $contentId = isset($cabecalhos['content-id'])
            ? trim($cabecalhos['content-id'], " <>\t")
            : null;

        return [[
            'mime' => $mime,
            'nome' => $nome !== null ? self::limparNome($nome) : null,
            'disposicao' => $disposicao !== '' ? $disposicao : 'inline',
            'contentId' => $contentId !== '' ? $contentId : null,
            'conteudo' => self::descodificarCorpo(
                $corpo,
                strtolower(trim($cabecalhos['content-transfer-encoding'] ?? '7bit'))
            ),
        ]];
    }

    /** @return list<string> */
    private static function dividirPorFronteira(string $corpo, string $fronteira): array
    {
        $marca = '--'.$fronteira;
        $tamanho = strlen($corpo);
        $segmentos = [];
        $inicio = null;
        $offset = 0;

        while ($offset <= $tamanho) {
            $posicao = strpos($corpo, $marca, $offset);

            if ($posicao === false) {
                break;
            }

            // A fronteira so' conta no inicio de uma linha.
            if ($posicao !== 0 && $corpo[$posicao - 1] !== "\n") {
                $offset = $posicao + strlen($marca);

                continue;
            }

            if ($inicio !== null) {
                $fim = $posicao;

                if ($fim > $inicio && $corpo[$fim - 1] === "\n") {
                    $fim--;

                    if ($fim > $inicio && $corpo[$fim - 1] === "\r") {
                        $fim--;
                    }
                }

                $segmentos[] = substr($corpo, $inicio, $fim - $inicio);
            }

            $depois = $posicao + strlen($marca);

            if (substr($corpo, $depois, 2) === '--') {
                break;
            }

            $quebra = strpos($corpo, "\n", $depois);

            if ($quebra === false) {
                break;
            }

            $inicio = $quebra + 1;
            $offset = $inicio;
        }

        return $segmentos;
    }

    /** @return array{0: string, 1: array<string, string>} */
    private static function lerTipoDeConteudo(string $valor): array
    {
        $valor = trim($valor);

        if ($valor === '') {
            return ['', []];
        }

        $pedacos = self::dividirParametros($valor);
        $mime = strtolower(trim(array_shift($pedacos) ?? ''));

        $parametros = [];
        $continuacoes = [];

        foreach ($pedacos as $pedaco) {
            if (! str_contains($pedaco, '=')) {
                continue;
            }

            [$chave, $conteudo] = explode('=', $pedaco, 2);
            $chave = strtolower(trim($chave));
            $conteudo = trim(trim($conteudo), '"');

            // RFC 2231: nome*0*=..., nome*1=..., nome*=utf-8''...
            if (preg_match('/^(.+?)\*(\d+)\*?$/', $chave, $m)) {
                $continuacoes[$m[1]][(int) $m[2]] = $conteudo;

                continue;
            }

            if (str_ends_with($chave, '*')) {
                $parametros[rtrim($chave, '*')] = self::descodificarRfc2231($conteudo);

                continue;
            }

            $parametros[$chave] = $conteudo;
        }

        foreach ($continuacoes as $chave => $pedacosOrdenados) {
            ksort($pedacosOrdenados);
            $juncao = implode('', $pedacosOrdenados);
            $parametros[$chave] = self::descodificarRfc2231($juncao);
        }

        return [$mime, $parametros];
    }

    /** Parte por ';' sem cortar dentro de aspas. */
    private static function dividirParametros(string $valor): array
    {
        $pedacos = [];
        $actual = '';
        $dentroDeAspas = false;

        for ($i = 0, $n = strlen($valor); $i < $n; $i++) {
            $letra = $valor[$i];

            if ($letra === '"' && ($i === 0 || $valor[$i - 1] !== '\\')) {
                $dentroDeAspas = ! $dentroDeAspas;
                $actual .= $letra;

                continue;
            }

            if ($letra === ';' && ! $dentroDeAspas) {
                $pedacos[] = trim($actual);
                $actual = '';

                continue;
            }

            $actual .= $letra;
        }

        if (trim($actual) !== '') {
            $pedacos[] = trim($actual);
        }

        return $pedacos;
    }

    private static function descodificarRfc2231(string $valor): string
    {
        // charset'lingua'texto-percent-encoded
        $conjunto = 'UTF-8';

        if (substr_count($valor, "'") >= 2) {
            [$conjunto, , $valor] = array_pad(explode("'", $valor, 3), 3, '');
            $conjunto = $conjunto !== '' ? $conjunto : 'UTF-8';
        }

        $texto = rawurldecode($valor);

        return self::paraUtf8($texto, $conjunto);
    }

    private static function descodificarCorpo(string $corpo, string $codificacao): string
    {
        return match ($codificacao) {
            'base64' => (string) base64_decode(preg_replace('/\s+/', '', $corpo) ?? '', false),
            'quoted-printable' => quoted_printable_decode($corpo),
            default => $corpo,
        };
    }

    public static function descodificarCabecalho(string $valor): string
    {
        if ($valor === '') {
            return '';
        }

        if (function_exists('iconv_mime_decode')) {
            $resultado = @iconv_mime_decode($valor, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');

            if (is_string($resultado) && $resultado !== '') {
                return $resultado;
            }
        }

        if (function_exists('mb_decode_mimeheader')) {
            return mb_decode_mimeheader($valor);
        }

        return $valor;
    }

    private static function paraUtf8(string $texto, string $conjunto): string
    {
        $conjunto = strtoupper(trim($conjunto));

        if ($conjunto === '' || $conjunto === 'UTF-8' || $conjunto === 'UTF8') {
            return $texto;
        }

        if (function_exists('mb_convert_encoding')) {
            $convertido = @mb_convert_encoding($texto, 'UTF-8', $conjunto);

            if (is_string($convertido) && $convertido !== '') {
                return $convertido;
            }
        }

        return $texto;
    }

    private static function limparNome(string $nome): string
    {
        $nome = self::descodificarCabecalho($nome);
        $nome = str_replace(['\\', '/', "\0"], '-', $nome);
        $nome = trim(preg_replace('/\s+/u', ' ', $nome) ?? $nome);

        return mb_substr($nome, 0, 180);
    }

    private static function extensaoDe(?string $nome, string $mime): string
    {
        $extensao = $nome !== null ? strtolower(pathinfo($nome, PATHINFO_EXTENSION)) : '';

        if ($extensao !== '') {
            return $extensao;
        }

        return match ($mime) {
            'application/pdf', 'application/x-pdf' => 'pdf',
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/tiff' => 'tiff',
            default => '',
        };
    }
}
