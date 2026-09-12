<?php

namespace App\Services\Contabilidade;

use App\Models\AccountingDocument;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Junta num zip os ficheiros de um conjunto de documentos, arrumados como o
 * contabilista os ve na pagina: Ano / Mes / Marca.
 *
 * A arrumacao nao e' enfeite. Ele fecha a contabilidade um mes de cada vez, e
 * um zip com trezentos PDF todos a mesma altura obriga-o a abrir cada um para
 * saber a que mes pertence — que e' exactamente o trabalho que este botao
 * existe para lhe poupar.
 */
class ZipDeDocumentos
{
    public function __construct(private AttachmentService $anexos)
    {
    }

    /**
     * Devolve `null` quando nenhum dos documentos tinha ficheiro nenhum — um
     * zip vazio parece um download que correu bem e nao e'.
     *
     * @param  \Illuminate\Support\Collection<int, AccountingDocument>  $documentos
     * @return array{caminho: string, ficheiros: int, documentos: int, semFicheiro: int}|null
     */
    public function construir($documentos): ?array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException(
                'A extensao zip do PHP nao esta instalada neste servidor (php-zip).'
            );
        }

        $caminho = tempnam(sys_get_temp_dir(), 'contabilidade-');

        if ($caminho === false) {
            throw new \RuntimeException('Nao consegui criar o ficheiro temporario do zip.');
        }

        $zip = new ZipArchive();

        if ($zip->open($caminho, ZipArchive::OVERWRITE) !== true) {
            @unlink($caminho);

            throw new \RuntimeException('Nao consegui abrir o zip para escrita.');
        }

        // As copias que vieram do NAS so' podem ser apagadas depois do
        // `close()`: e' ai que o ZipArchive as le de facto do disco.
        $temporarios = [];
        $usados      = [];
        $ficheiros   = 0;
        $semFicheiro = 0;

        foreach ($documentos as $documento) {
            $pasta = $this->pastaDe($documento);
            $base  = $this->nomeBaseDe($documento, $usados);
            $antes = $ficheiros;

            $temPrincipal = false;

            if ($documento->file_path && Storage::disk('public')->exists($documento->file_path)) {
                $extensao = strtolower(pathinfo($documento->file_path, PATHINFO_EXTENSION)) ?: 'pdf';

                $juntou = $zip->addFile(
                    Storage::disk('public')->path($documento->file_path),
                    "{$pasta}/{$base}.{$extensao}"
                );

                if ($juntou) {
                    $temPrincipal = true;
                    $ficheiros++;
                }
            }

            // Sem PDF, a foto E' a factura: fica a vista, ao lado das outras.
            // Com PDF, e' so' a mesma coisa fotografada — vai para o fundo,
            // com os acompanhantes.
            foreach (array_values($documento->image_paths ?? []) as $indice => $imagem) {
                if (! $imagem || ! Storage::disk('public')->exists($imagem)) {
                    continue;
                }

                $extensao = strtolower(pathinfo($imagem, PATHINFO_EXTENSION)) ?: 'jpg';
                $numero   = $indice + 1;

                $destino = $temPrincipal
                    ? "{$pasta}/anexos/{$base}/foto-{$numero}.{$extensao}"
                    : "{$pasta}/{$base}-foto-{$numero}.{$extensao}";

                if ($zip->addFile(Storage::disk('public')->path($imagem), $destino)) {
                    $ficheiros++;
                }
            }

            // Os acompanhantes do mesmo email — o detalhe das passagens, o CSV,
            // o XML. Ele exigiu ter todos; ficam numa subpasta com o nome da
            // factura para nao se perder qual pertence a qual.
            foreach ($documento->anexos as $anexo) {
                $leitura = $this->anexos->caminhoParaLeitura($anexo);

                if ($leitura === null) {
                    continue;
                }

                if ($leitura['temporario']) {
                    $temporarios[] = $leitura['caminho'];
                }

                $nome = $this->seguro((string) $anexo->original_name, 'anexo');

                if ($zip->addFile($leitura['caminho'], "{$pasta}/anexos/{$base}/{$nome}")) {
                    $ficheiros++;
                }
            }

            if ($ficheiros === $antes) {
                $semFicheiro++;
            }
        }

        // Um `close()` que falha deixa um ficheiro incompleto no disco — e o
        // download seguia na mesma, com um zip que so' da erro ao abrir.
        $fechou = $zip->close();

        foreach ($temporarios as $temporario) {
            @unlink($temporario);
        }

        if (! $fechou) {
            @unlink($caminho);

            throw new \RuntimeException('O zip nao chegou a ser escrito ate ao fim.');
        }

        if ($ficheiros === 0) {
            @unlink($caminho);

            return null;
        }

        return [
            'caminho'     => $caminho,
            'ficheiros'   => $ficheiros,
            'documentos'  => $documentos->count(),
            'semFicheiro' => $semFicheiro,
        ];
    }

    /** Ano / mes com numero a frente (para ordenar) / marca. */
    private function pastaDe(AccountingDocument $documento): string
    {
        $ano = (int) ($documento->date?->year ?: $documento->year);
        $mes = (int) ($documento->date?->month ?: $documento->month);

        $nomeDoMes = AccountingDocument::monthName($mes);

        $pastaAno = $ano > 0 ? (string) $ano : 'sem-data';
        $pastaMes = $mes > 0
            ? sprintf('%02d-%s', $mes, $this->seguro($nomeDoMes, 'mes'))
            : 'sem-mes';

        $marca = $documento->brand?->full_name ?: 'Sem marca';

        return $pastaAno.'/'.$pastaMes.'/'.$this->seguro((string) $marca, 'Sem marca');
    }

    /**
     * `2026-09-15_Via Verde_FT 2026-123`. Data a frente porque e' assim que ele
     * quer a lista dentro da pasta do mes.
     *
     * @param  array<string, bool>  $usados
     */
    private function nomeBaseDe(AccountingDocument $documento, array &$usados): string
    {
        $fornecedor = (string) ($documento->fornecedor
            ?: AccountingDocument::finalidadeLabel($documento->title));

        $partes = array_filter([
            $documento->date?->format('Y-m-d'),
            $this->seguro($fornecedor, ''),
            $this->seguro((string) $documento->invoice_number, ''),
        ]);

        $base = $this->seguro(implode('_', $partes), 'documento');

        // Duas facturas do mesmo fornecedor, no mesmo dia, sem numero, dao o
        // mesmo nome — e a segunda substituia a primeira dentro do zip, sem
        // erro nenhum. Ele so' daria por isso a fechar o mes.
        if (isset($usados[$base])) {
            $base .= '_'.$documento->id;
        }

        $usados[$base] = true;

        return $base;
    }

    /**
     * Nome que sobrevive a um duplo clique no Explorador do Windows: sem
     * acentos (o zip nao carrega a codificacao com ele) e sem os caracteres
     * que o Windows recusa em nomes de ficheiro.
     */
    private function seguro(string $texto, string $porOmissao = 'sem-nome'): string
    {
        $limpo = Str::ascii($texto);
        $limpo = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '-', $limpo);
        $limpo = (string) preg_replace('/[\x00-\x1F]+/', '', $limpo);
        $limpo = (string) preg_replace('/\s+/', ' ', $limpo);
        $limpo = trim($limpo, " .-\t");

        if ($limpo === '') {
            return $porOmissao;
        }

        return mb_substr($limpo, 0, 80);
    }
}
