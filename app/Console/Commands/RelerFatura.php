<?php

namespace App\Console\Commands;

use App\Models\AccountingDocument;
use App\Services\PaperInvoice\PaperInvoiceExtractor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Volta a ler os ficheiros de um documento que ja esta no painel.
 *
 * Sem isto, corrigir o leitor nao servia de nada ao que ja tinha entrado: o
 * `ficheiro_hash` impede a reimportacao — e bem, senao duplicava — e portanto
 * um documento criado com um total errado ficava com ele para sempre. Foi o que
 * aconteceu com o extracto da Via Verde de Agosto: entrou a 0,00 EUR antes de o
 * leitor saber ler "Total em Euros", e nao havia maneira de o mandar reler.
 *
 * Nao toca na Finalidade, na Marca, na Categoria nem no campo do contabilista:
 * essas sao decisoes de quem ca esta, nao leituras do ficheiro.
 */
class RelerFatura extends Command
{
    protected $signature = 'faturas:reler
        {documento? : ID do documento a reler}
        {--por-rever : Reler todos os que estao em "Por rever"}
        {--zerados : Reler todos os que ficaram a 0,00 EUR}
        {--sim : Aplicar sem perguntar}';

    protected $description = 'Le outra vez os ficheiros de um documento e corrige os campos lidos';

    public function handle(PaperInvoiceExtractor $extractor): int
    {
        $documentos = $this->escolherDocumentos();

        if ($documentos->isEmpty()) {
            $this->warn('Nao ha documentos que correspondam. Indica um ID, ou usa --por-rever / --zerados.');

            return self::FAILURE;
        }

        $this->info($documentos->count().' documento(s) a reler.');

        $alterados = 0;

        foreach ($documentos as $documento) {
            if ($this->relerUm($documento, $extractor)) {
                $alterados++;
            }
        }

        $this->newLine();
        $this->info("{$alterados} documento(s) actualizados.");

        return self::SUCCESS;
    }

    private function escolherDocumentos()
    {
        if ($id = $this->argument('documento')) {
            return AccountingDocument::with('anexos')->where('id', $id)->get();
        }

        $query = AccountingDocument::with('anexos')->where('origem', 'email');

        if ($this->option('por-rever')) {
            $query->where('estado', 'por_rever');
        } elseif ($this->option('zerados')) {
            $query->where('amount_cents', '<=', 0);
        } else {
            return AccountingDocument::whereRaw('1 = 0')->get();
        }

        return $query->orderBy('id')->get();
    }

    private function relerUm(AccountingDocument $documento, PaperInvoiceExtractor $extractor): bool
    {
        $this->newLine();
        $this->info("── Documento {$documento->id} · ".($documento->email_assunto ?: $documento->title));

        $leitura = $this->lerFicheiros($documento, $extractor);

        if ($leitura === null) {
            $this->warn('  Sem ficheiros legiveis no disco. Nada a fazer.');

            return false;
        }

        $novos = $this->camposLidos($documento, $leitura);
        $mudancas = [];

        foreach ($novos as $campo => $valor) {
            if ((string) $documento->{$campo} !== (string) $valor) {
                $mudancas[$campo] = [$documento->{$campo}, $valor];
            }
        }

        if ($mudancas === []) {
            $this->line('  Nada mudou.');

            return false;
        }

        $this->table(
            ['Campo', 'Antes', 'Depois'],
            array_map(
                fn (string $campo, array $par) => [
                    $campo,
                    $this->paraMostrar($campo, $par[0]),
                    $this->paraMostrar($campo, $par[1]),
                ],
                array_keys($mudancas),
                $mudancas,
            ),
        );

        if (! $this->option('sim') && ! $this->confirm('Aplicar?', true)) {
            $this->line('  Deixado como estava.');

            return false;
        }

        $documento->forceFill($novos)->save();
        $this->info('  Actualizado.');

        return true;
    }

    /**
     * O PDF principal, as fotos, e os anexos que se conseguem ler.
     *
     * Vale a leitura com o maior total, pela mesma razao do importador: um
     * documento nao pode valer menos do que a sua propria decomposicao.
     */
    private function lerFicheiros(AccountingDocument $documento, PaperInvoiceExtractor $extractor): ?array
    {
        $caminhos = array_filter(array_merge(
            [$documento->file_path],
            array_values((array) ($documento->image_paths ?? [])),
            $documento->anexos
                ->filter(fn ($anexo) => $anexo->storage_type === 'local' && $anexo->isPreviewable())
                ->pluck('file_path')
                ->all(),
        ));

        $melhor = null;

        foreach ($caminhos as $caminho) {
            if (! Storage::disk('public')->exists($caminho)) {
                continue;
            }

            try {
                $lido = $extractor->extract(Storage::disk('public')->path($caminho));
            } catch (\Throwable $e) {
                $this->warn('  '.basename($caminho).': '.$e->getMessage());

                continue;
            }

            $this->line(sprintf(
                '  %s -> total %s',
                basename($caminho),
                number_format((float) ($lido['invoice']['total'] ?? 0), 2, ',', '.'),
            ));

            if ($melhor === null || ($lido['invoice']['total'] ?? 0) > ($melhor['invoice']['total'] ?? 0)) {
                $melhor = $lido;
            }
        }

        return $melhor;
    }

    /** @return array<string, mixed> */
    private function camposLidos(AccountingDocument $documento, array $leitura): array
    {
        $factura = $leitura['invoice'] ?? [];
        $fornecedor = $leitura['supplier'] ?? [];

        $total = (int) round(((float) ($factura['total'] ?? 0)) * 100);
        $nif = trim((string) ($fornecedor['taxNumber'] ?? ''));

        $campos = [
            'invoice_number' => ($factura['number'] ?: null) ?? $documento->invoice_number,
            'supplier_nif' => ($nif ?: null) ?? $documento->supplier_nif,
            'atcud' => ($factura['atcud'] ?: null) ?? $documento->atcud,
            'fornecedor' => AccountingDocument::fornecedorPorNif($nif)
                ?: (trim((string) ($fornecedor['name'] ?? '')) ?: $documento->fornecedor),
            'amount_cents' => $total > 0 ? $total : $documento->amount_cents,
            'iva_cents' => (int) round(((float) ($factura['vatTotal'] ?? 0)) * 100) ?: $documento->iva_cents,
        ];

        if ($data = $this->data($factura['date'] ?? null)) {
            $campos['date'] = $data;
        }

        // Um documento que passou a ter total deixa de precisar de revisao —
        // era exactamente a falta dele que o tinha posto la.
        if ($total > 0 && $documento->estado === 'por_rever') {
            $campos['estado'] = 'pendente';
        }

        return $campos;
    }

    private function data(?string $data): ?string
    {
        if (! $data) {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, $data)->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function paraMostrar(string $campo, mixed $valor): string
    {
        if (in_array($campo, ['amount_cents', 'iva_cents'], true)) {
            return number_format(((int) $valor) / 100, 2, ',', '.').' EUR';
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('d/m/Y');
        }

        return (string) ($valor ?? '—') ?: '—';
    }
}
