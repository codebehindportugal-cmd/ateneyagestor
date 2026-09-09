<?php

namespace App\Console\Commands;

use App\Models\AccountingDocument;
use App\Services\PaperInvoice\PaperInvoiceExtractor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Porque e' que este documento ficou por rever?
 *
 * Quando uma factura entra a zero, ha tres explicacoes possiveis e do painel
 * nao se distinguem: falta um binario no servidor, o PDF e' uma imagem sem
 * texto, ou o texto saiu bem mas o padrao do total nao bate certo com o
 * formato daquele fornecedor. Este comando mostra as tres de uma vez.
 */
class DiagnosticarFatura extends Command
{
    protected $signature = 'faturas:diagnosticar
        {documento : ID do documento de contabilidade}
        {--texto=1500 : Quantos caracteres do texto lido mostrar}';

    protected $description = 'Mostra o que o leitor conseguiu tirar dos ficheiros de um documento';

    public function handle(PaperInvoiceExtractor $extractor): int
    {
        $documento = AccountingDocument::with('anexos')->find($this->argument('documento'));

        if (! $documento) {
            $this->error('Documento nao encontrado.');

            return self::FAILURE;
        }

        $this->line('');
        $this->info("Documento {$documento->id} · {$documento->email_assunto}");
        $this->line("  Estado: {$documento->estado} · Total: ".number_format($documento->amount, 2, ',', '.').' EUR');
        $this->line('  De: '.($documento->email_de ?: '—'));

        $this->line('');
        $this->info('Ferramentas no servidor');

        foreach (['pdftotext', 'pdftoppm', 'zbarimg', 'tesseract'] as $binario) {
            $caminho = trim((string) shell_exec('command -v '.escapeshellarg($binario).' 2>/dev/null'));
            $this->line(sprintf('  %-10s %s', $binario, $caminho !== '' ? $caminho : 'EM FALTA'));
        }

        $ficheiros = [];

        if ($documento->file_path) {
            $ficheiros[] = ['nome' => $documento->file_name ?: basename($documento->file_path), 'caminho' => $documento->file_path];
        }

        foreach (($documento->image_paths ?? []) as $indice => $imagem) {
            $ficheiros[] = ['nome' => $documento->image_names[$indice] ?? basename($imagem), 'caminho' => $imagem];
        }

        foreach ($documento->anexos as $anexo) {
            // Um CSV ou um XML nao se leem com o tesseract. Atirar-lhos gerava
            // meia pagina de erro do leitor de imagens e escondia o resto.
            if (! $anexo->isPreviewable()) {
                $this->line('');
                $this->line("── {$anexo->original_name}");
                $this->line('  Ficheiro de dados ('.$anexo->mime_type.') — nao passa pelo leitor de facturas.');

                continue;
            }

            if ($anexo->storage_type !== 'local') {
                $this->line('');
                $this->warn("Anexo {$anexo->original_name} esta no NAS — nao o leio daqui.");

                continue;
            }

            $ficheiros[] = ['nome' => $anexo->original_name, 'caminho' => $anexo->file_path];
        }

        if ($ficheiros === []) {
            $this->line('');
            $this->warn('Este documento nao tem ficheiros no disco do servidor.');

            return self::SUCCESS;
        }

        foreach ($ficheiros as $ficheiro) {
            $this->mostrarFicheiro($ficheiro['nome'], $ficheiro['caminho'], $extractor);
        }

        return self::SUCCESS;
    }

    private function mostrarFicheiro(string $nome, string $caminhoRelativo, PaperInvoiceExtractor $extractor): void
    {
        $this->line('');
        $this->info("── {$nome}");

        if (! Storage::disk('public')->exists($caminhoRelativo)) {
            $this->error("  Ficheiro nao existe em storage/app/public/{$caminhoRelativo}");

            return;
        }

        $absoluto = Storage::disk('public')->path($caminhoRelativo);
        $this->line('  Caminho: '.$caminhoRelativo.' ('.number_format(filesize($absoluto) / 1024, 1, ',', '.').' KB)');

        try {
            $resultado = $extractor->extract($absoluto);
        } catch (\Throwable $e) {
            $this->error('  A leitura rebentou: '.$e->getMessage());

            return;
        }

        $texto = (string) ($resultado['rawText'] ?? '');

        $this->line('  Fornecedor lido: '.($resultado['supplier']['name'] ?: '—'));
        $this->line('  NIF: '.($resultado['supplier']['taxNumber'] ?: '—'));
        $this->line('  Numero: '.($resultado['invoice']['number'] ?: '—'));
        $this->line('  Data: '.($resultado['invoice']['date'] ?: '—'));
        $this->line('  TOTAL: '.($resultado['invoice']['total'] ?: '0').'   IVA: '.($resultado['invoice']['vatTotal'] ?: '0'));
        $this->line('  ATCUD: '.($resultado['invoice']['atcud'] ?: '—'));
        $this->line('  Texto lido: '.strlen($texto).' caracteres');

        $this->mostrarQr((string) ($resultado['qrData'] ?? ''));
        $this->mostrarCandidatosATotal($texto);

        if (($resultado['warnings'] ?? []) !== []) {
            $this->warn('  Avisos:');

            foreach ($resultado['warnings'] as $aviso) {
                $this->line('    - '.$aviso);
            }
        }

        if ($texto === '') {
            $this->error('  Nao saiu texto nenhum. PDF digitalizado sem OCR, protegido, ou falta o pdftotext/tesseract.');

            return;
        }

        $this->line('');
        $this->line('  ── texto lido ──');
        $this->line(mb_substr($texto, 0, (int) $this->option('texto')));
        $this->line('  ── fim ──');
    }

    /**
     * O QR das facturas portuguesas e' a fonte mais fiavel que ha: traz o NIF,
     * o numero, a data e o total sem depender de nenhuma expressao regular.
     * Quando ele existe e mesmo assim os campos vem vazios, e' porque o codigo
     * que zbarimg leu nao e' o da AT — pode ser um QR de pagamento, ou um link.
     * Sem ver o conteudo nao ha maneira de distinguir os dois casos.
     */
    private function mostrarQr(string $qrData): void
    {
        if ($qrData === '') {
            $this->line('  QR code: nao encontrado');

            return;
        }

        $this->line('  QR code: encontrado ('.strlen($qrData).' caracteres)');
        $this->line('  ── conteudo do QR ──');
        $this->line('  '.mb_substr($qrData, 0, 900));

        $campos = [];

        foreach (explode('*', $qrData) as $par) {
            $corte = strpos($par, ':');

            if ($corte !== false) {
                $campos[trim(substr($par, 0, $corte))] = trim(substr($par, $corte + 1));
            }
        }

        if ($campos === []) {
            $this->error('  Este QR nao tem campos no formato da AT (A:...*B:...). Nao serve para ler a factura.');

            return;
        }

        $this->line('  Campos: '.implode(', ', array_keys($campos)));
        $this->line('    A (NIF emitente): '.($campos['A'] ?? '— em falta'));
        $this->line('    D (tipo): '.($campos['D'] ?? '— em falta'));
        $this->line('    F (data): '.($campos['F'] ?? '— em falta'));
        $this->line('    G (numero): '.($campos['G'] ?? '— em falta'));
        $this->line('    H (ATCUD): '.($campos['H'] ?? '— em falta'));
        $this->line('    N (total IVA): '.($campos['N'] ?? '— em falta'));
        $this->line('    O (total c/ IVA): '.($campos['O'] ?? '— em falta'));
    }

    /**
     * As linhas onde o total provavelmente esta. Cada fornecedor escreve-o a
     * sua maneira — "Total a pagar", "Importancia", "Total do documento" — e a
     * expressao que o procura so' se corrige depois de se ver como aquele
     * fornecedor o escreve.
     */
    private function mostrarCandidatosATotal(string $texto): void
    {
        $linhas = preg_split('/\R/u', $texto) ?: [];
        $candidatas = [];

        foreach ($linhas as $linha) {
            $linha = trim(preg_replace('/\s+/u', ' ', $linha) ?? '');

            if ($linha === '' || ! preg_match('/\d+[.,]\d{2}/', $linha)) {
                continue;
            }

            if (preg_match('/total|pagar|import[aâ]ncia|iva|euros?|EUR|€|liquido|l[ií]quido/iu', $linha)) {
                $candidatas[] = $linha;
            }
        }

        if ($candidatas === []) {
            $this->warn('  Nenhuma linha com aspecto de total.');

            return;
        }

        $this->line('');
        $this->line('  ── linhas com aspecto de total ──');

        foreach (array_slice($candidatas, -20) as $linha) {
            $this->line('    '.mb_substr($linha, 0, 160));
        }
    }
}
