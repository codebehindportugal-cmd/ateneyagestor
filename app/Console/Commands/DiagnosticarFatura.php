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
        $this->line('  QR code: '.(($resultado['qrData'] ?? '') !== '' ? 'encontrado' : 'nao encontrado'));
        $this->line('  Texto lido: '.strlen($texto).' caracteres');

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
}
