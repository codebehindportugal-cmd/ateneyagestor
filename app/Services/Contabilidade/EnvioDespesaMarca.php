<?php

namespace App\Services\Contabilidade;

use App\Models\AccountingDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Manda um documento de despesa para o painel da marca (01/10/2026).
 *
 * Hoje: as faturas da Horta da Maria seguem para a gestao.hortadamaria.com
 * (POST /api/v1/faturas, a mesma API que o chat usa) e o PDF a seguir
 * (/api/v1/faturas/{id}/ficheiro). Serve para qualquer marca que tenha na
 * ficha o endereço e a chave da API dela.
 *
 * Seguro de repetir: vai com origem "gestao.ateneya.com" e o id do documento,
 * e do outro lado o mesmo par devolve a despesa que já existe em vez de criar
 * outra. Os recibos de vencimento não têm número de fatura — é por isso.
 *
 * Só uma linha: o total sem IVA à taxa que o IVA do documento dá. A leitura das
 * linhas de produtos ainda não é de confiança, e para gasóleo, portagens ou
 * ordenados uma linha é o que interessa. O total com IVA vai à parte, por isso
 * o valor bate certo mesmo com taxas misturadas.
 */
class EnvioDespesaMarca
{
    public const ORIGEM = 'gestao.ateneya.com';

    /** Taxas que o outro lado aceita. */
    private const TAXAS = [0, 6, 13, 23];

    /** Categoria daqui → categoria da Despesa lá (App\Models\Despesa::CATEGORIAS). */
    private const CATEGORIAS = [
        'fornecedores'  => 'compras',
        'mercadorias'   => 'entrada_produtos',
        'combustiveis'  => 'combustivel',
        'viaturas'      => 'viaturas',
        'ordenados'     => 'ordenados',
        'servicos'      => 'servicos',
        'software'      => 'servicos',
        'comunicacoes'  => 'servicos',
        'publicidade'   => 'servicos',
        'contabilidade' => 'servicos',
        'material'      => 'equipamento',
    ];

    /** Fornecedores de portagens: dentro de "Viaturas" separam-se do resto. */
    private const PORTAGENS = ['via verde', 'brisa', 'ascendi', 'portagens', 'infraestruturas de portugal', 'easytoll'];

    public static function deveSeguir(AccountingDocument $doc): bool
    {
        return $doc->estado !== 'por_rever'
            && $doc->enviado_marca_em === null
            && $doc->brand?->recebeDespesas() === true;
    }

    /** @return array<string, mixed> */
    public function payload(AccountingDocument $doc): array
    {
        $total = round($doc->amount_cents / 100, 2);
        $iva = round(($doc->iva_cents ?? 0) / 100, 2);
        $semIva = round($total - $iva, 2);

        $taxa = 0;
        if ($iva > 0 && $semIva > 0) {
            $real = $iva / $semIva * 100;
            $taxa = collect(self::TAXAS)->sortBy(fn ($t) => abs($t - $real))->first();
        }

        $descricao = $doc->title ?: (AccountingDocument::categories()[$doc->category] ?? 'Despesa');

        return array_filter([
            'titulo'        => $doc->title ?: null,
            'numero_fatura' => $doc->invoice_number ?: null,
            'fornecedor'    => $doc->fornecedor ?: null,
            'data'          => $doc->date?->toDateString(),
            'valor'         => $total,
            'categoria'     => $this->categoria($doc),
            'viatura'       => $doc->viatura ?: null,
            'notas'         => trim(($doc->notes ?? '') . "\nVeio da gestao.ateneya.com (documento #{$doc->id})."),
            'origem'        => self::ORIGEM,
            'origem_ref'    => (string) $doc->id,
            'linhas'        => [[
                'descricao'        => mb_substr($descricao, 0, 255),
                'quantidade'       => 1,
                'preco_unitario'   => $taxa > 0 ? round($total / (1 + $taxa / 100), 4) : $total,
                'iva_percentagem'  => $taxa,
            ]],
        ], fn ($v) => $v !== null);
    }

    public function categoria(AccountingDocument $doc): string
    {
        if ($doc->category === 'viaturas') {
            $quem = mb_strtolower(($doc->fornecedor ?? '') . ' ' . ($doc->title ?? ''));
            foreach (self::PORTAGENS as $nome) {
                if (str_contains($quem, $nome)) {
                    return 'portagens';
                }
            }
        }

        // Um seguro com matrícula é do carro.
        if ($doc->category === 'seguros' && filled($doc->viatura)) {
            return 'viaturas';
        }

        return self::CATEGORIAS[$doc->category] ?? 'outro';
    }

    /**
     * Envia, guarda o id de lá e manda o ficheiro. Lança excepção se falhar,
     * depois de deixar a razão em enviado_marca_erro (o job tenta outra vez).
     */
    public function enviar(AccountingDocument $doc): void
    {
        $marca = $doc->brand;
        $base = rtrim((string) $marca->despesas_api_url, '/');

        try {
            $resposta = Http::withToken((string) $marca->despesas_api_token)
                ->acceptJson()
                ->timeout(30)
                ->post("{$base}/api/v1/faturas", $this->payload($doc));

            if (! $resposta->successful() || ! $resposta->json('sucesso')) {
                throw new \RuntimeException("HTTP {$resposta->status()}: " . mb_substr($resposta->body(), 0, 500));
            }

            $idLa = (string) $resposta->json('dados.despesa.id');
            $avisos = implode(' ', (array) $resposta->json('avisos', []));

            $doc->forceFill([
                'enviado_marca_em'   => now(),
                'enviado_marca_ref'  => $idLa,
                'enviado_marca_erro' => $avisos !== '' ? "Aviso de lá: {$avisos}" : null,
            ])->saveQuietly();

            $this->enviarFicheiro($doc, $base, (string) $marca->despesas_api_token, $idLa);
        } catch (\Throwable $e) {
            $doc->forceFill(['enviado_marca_erro' => mb_substr($e->getMessage(), 0, 1000)])->saveQuietly();

            throw $e;
        }
    }

    /** O PDF (ou a primeira foto). Falhar aqui não desfaz a despesa: fica o aviso. */
    private function enviarFicheiro(AccountingDocument $doc, string $base, string $token, string $idLa): void
    {
        $caminho = $doc->file_path ?: ($doc->image_paths[0] ?? null);

        if (! $caminho || ! Storage::disk('public')->exists($caminho)) {
            return;
        }

        $nome = $doc->file_path ? ($doc->file_name ?: basename($caminho)) : basename($caminho);

        $resposta = Http::withToken($token)
            ->acceptJson()
            ->timeout(60)
            ->attach('ficheiro', Storage::disk('public')->get($caminho), $nome)
            ->post("{$base}/api/v1/faturas/{$idLa}/ficheiro");

        if (! $resposta->successful()) {
            $doc->forceFill([
                'enviado_marca_erro' => "A despesa foi criada (#{$idLa}) mas o ficheiro não: HTTP {$resposta->status()}",
            ])->saveQuietly();
        }
    }
}
