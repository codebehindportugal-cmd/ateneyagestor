<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreFaturaApiRequest;
use App\Http\Requests\Api\StoreFaturasLoteApiRequest;
use App\Models\AccountingDocument;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Ingestao de faturas de fornecedor pela API — /api/v1/faturas.
 *
 * Escreve no mesmo AccountingDocument que o importador de email alimenta, com
 * `origem = api`. O importador de email nao e tocado: continua a ser a via das
 * faturas que chegam em PDF na caixa de correio; esta e a via das que chegam em
 * papel e sao lidas de uma foto. Um documento so existe uma vez — antes de
 * criar procura-se pelo numero + NIF, por isso uma fatura que ja veio por email
 * nao entra outra vez por aqui.
 *
 * As faturas entram em `pendente`, ou seja visiveis ao contabilista (`por_rever`
 * e o estado das que ninguem percebeu, e nao e para aqui). Quem envia pode pedir
 * outro estado no campo `estado`.
 */
class FaturaController extends Controller
{
    use RespondeJson;

    public function store(StoreFaturaApiRequest $request): JsonResponse
    {
        $utilizador = $this->utilizadorAutenticado($request);
        $data = $request->validated();
        $avisos = [];

        $existente = $this->jaRegistado($data);

        if ($existente !== null) {
            $avisos[] = sprintf(
                'fatura ja registada (%s, origem %s)',
                $data['numero_fatura'] ?? '(sem numero)',
                $existente->origem ?: 'desconhecida'
            );

            return $this->criado($this->formatar($existente), $avisos);
        }

        try {
            [$documento, $avisosCriacao] = $this->registar($data, $utilizador);
        } catch (ValidationException $excepcao) {
            return $this->erro422($excepcao->errors());
        }

        return $this->criado($this->formatar($documento), array_merge($avisos, $avisosCriacao));
    }

    /**
     * Varias faturas num pedido — a pilha de papel fotografada de seguida.
     *
     * Cada uma e validada e gravada por si, em transaccao propria: uma que falhe
     * devolve o erro dela e as outras entram. 201 quando tudo passou, 207 quando
     * o lote foi parcial, 422 quando nao entrou nada.
     */
    public function lote(StoreFaturasLoteApiRequest $request): JsonResponse
    {
        $utilizador = $this->utilizadorAutenticado($request);

        // input() e nao validated(): o pedido so valida o envelope e cada fatura
        // e validada em baixo com as regras completas.
        $faturas = (array) $request->input('faturas', []);

        $resultados = [];
        $erros = [];
        $registadas = 0;
        $repetidas = 0;
        $falhadas = 0;

        foreach (array_values($faturas) as $indice => $fatura) {
            $referencia = is_array($fatura) ? ($fatura['numero_fatura'] ?? null) : null;

            $validador = ValidatorFacade::make(
                is_array($fatura) ? $fatura : [],
                StoreFaturaApiRequest::regrasFatura(),
                StoreFaturaApiRequest::mensagensFatura()
            );

            if ($validador->fails()) {
                $falhadas++;
                $erros["faturas.{$indice}"] = $validador->errors()->toArray();
                $resultados[] = $this->resultadoLote($indice, $referencia, 'erro', null, [], $validador->errors()->toArray());

                continue;
            }

            $data = $validador->validated();
            $referencia = $data['numero_fatura'] ?? null;

            $existente = $this->jaRegistado($data);

            if ($existente !== null) {
                $repetidas++;
                $resultados[] = $this->resultadoLote(
                    $indice,
                    $referencia,
                    'repetida',
                    $this->formatar($existente),
                    [sprintf('fatura ja registada (%s, origem %s)', $referencia ?? '(sem numero)', $existente->origem ?: 'desconhecida')]
                );

                continue;
            }

            try {
                [$documento, $avisosCriacao] = $this->registar($data, $utilizador);

                $registadas++;
                $resultados[] = $this->resultadoLote(
                    $indice,
                    $referencia,
                    'registada',
                    $this->formatar($documento),
                    $avisosCriacao
                );
            } catch (ValidationException $excepcao) {
                $falhadas++;
                $erros["faturas.{$indice}"] = $excepcao->errors();
                $resultados[] = $this->resultadoLote($indice, $referencia, 'erro', null, [], $excepcao->errors());
            } catch (Throwable $excepcao) {
                // Cada fatura tem a sua transaccao, por isso as anteriores
                // entraram. Devolver 500 aqui deixava quem enviou sem saber
                // quais — e a repetir o lote todo por causa de uma.
                Log::error('falha ao registar fatura do lote', [
                    'indice' => $indice,
                    'numero_fatura' => $referencia,
                    'excepcao' => $excepcao,
                ]);

                $mensagem = ['fatura' => ['Erro inesperado ao registar: '.$excepcao->getMessage()]];

                $falhadas++;
                $erros["faturas.{$indice}"] = $mensagem;
                $resultados[] = $this->resultadoLote($indice, $referencia, 'erro', null, [], $mensagem);
            }
        }

        $total = count($resultados);

        $estado = match (true) {
            $falhadas === 0 => 201,
            $registadas + $repetidas > 0 => 207,
            default => 422,
        };

        return response()->json([
            'sucesso' => $falhadas === 0,
            'dados' => [
                'total' => $total,
                'registadas' => $registadas,
                'repetidas' => $repetidas,
                'falhadas' => $falhadas,
                'faturas' => $resultados,
            ],
            'avisos' => [sprintf(
                '%d faturas: %d registadas, %d repetidas, %d falhadas.',
                $total,
                $registadas,
                $repetidas,
                $falhadas
            )],
            'erros' => $erros,
        ], $estado);
    }

    /**
     * A foto ou o PDF da fatura, para um documento que ja existe.
     *
     * Separado do store porque o corpo deste e o ficheiro e o do store e JSON:
     * uma foto de telemovel nao cabe numa mensagem de texto. Guarda no mesmo
     * disco e nas mesmas pastas que o importador de email usa, para o painel e o
     * portal do contabilista mostrarem as duas vias da mesma maneira.
     */
    public function ficheiro(Request $request, AccountingDocument $documento): JsonResponse
    {
        $this->utilizadorAutenticado($request);

        try {
            $request->validate([
                'ficheiro' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:20480'],
            ]);
        } catch (ValidationException $excepcao) {
            return $this->erro422($excepcao->errors());
        }

        $ficheiro = $request->file('ficheiro');
        $avisos = [];
        $ehPdf = strtolower($ficheiro->getClientOriginalExtension() ?: $ficheiro->extension()) === 'pdf';

        $data = $documento->date ?? Carbon::now();
        $pasta = ($ehPdf ? 'accounting-documents' : 'accounting-document-images').'/api/'.$data->format('Y/m');

        $conteudo = file_get_contents($ficheiro->getRealPath());
        $hash = sha1($conteudo);

        $nome = Str::limit(Str::slug(pathinfo($ficheiro->getClientOriginalName(), PATHINFO_FILENAME)), 60, '')
            .'-'.substr($hash, 0, 8).'.'.($ehPdf ? 'pdf' : strtolower($ficheiro->getClientOriginalExtension() ?: 'jpg'));

        $destino = $pasta.'/'.$nome;

        // Substituir apaga o anterior: uma imagem orfa no disco nunca mais e
        // vista por ninguem e ninguem sabe de quem e'.
        foreach (array_filter([$ehPdf ? $documento->file_path : null, ...($ehPdf ? [] : ($documento->image_paths ?? []))]) as $antigo) {
            if (Storage::disk('public')->exists($antigo)) {
                Storage::disk('public')->delete($antigo);
                $avisos[] = 'o ficheiro anterior foi substituido.';
            }
        }

        Storage::disk('public')->put($destino, $conteudo);

        if ($ehPdf) {
            $documento->file_path = $destino;
            $documento->file_name = $ficheiro->getClientOriginalName();
        } else {
            $documento->image_paths = [$destino];
            $documento->image_names = [$ficheiro->getClientOriginalName()];
        }

        // O hash e o que impede o importador de email de trazer outra vez o
        // mesmo ficheiro, se ele chegar tambem por correio.
        if (blank($documento->ficheiro_hash)) {
            $documento->ficheiro_hash = $hash;
        }

        $documento->save();

        return $this->ok([
            'documento_id' => $documento->id,
            'numero_fatura' => $documento->invoice_number,
            'ficheiro_path' => $destino,
            'ficheiro_url' => Storage::disk('public')->url($destino),
        ], $avisos);
    }

    /**
     * O token tem de ser de um utilizador do painel, e administrador.
     *
     * Nao basta a ability: os agentes de backup e os sincronizadores tambem tem
     * tokens neste projecto, muitos com `*`, e nenhum deles tem nada que andar a
     * lancar despesas na contabilidade.
     */
    private function utilizadorAutenticado(Request $request): User
    {
        $tokenable = $request->user();

        abort_unless($tokenable instanceof User, 403, 'Token nao pertence a um utilizador do painel.');
        abort_unless($tokenable->isAdmin(), 403, 'So um administrador pode registar faturas.');

        return $tokenable;
    }

    /**
     * O documento que esta fatura ja criou, se existir.
     *
     * Procura pelo numero e pelo NIF (ou pelo nome do fornecedor, quando a
     * fatura nao traz NIF) em qualquer origem: o objectivo e nao duplicar uma
     * fatura que ja entrou por email.
     *
     * @param  array<string, mixed>  $data
     */
    private function jaRegistado(array $data): ?AccountingDocument
    {
        if (empty($data['numero_fatura'])) {
            return null;
        }

        $nif = trim((string) ($data['nif'] ?? ''));
        $fornecedor = trim((string) ($data['fornecedor'] ?? ''));

        return AccountingDocument::query()
            ->where('invoice_number', $data['numero_fatura'])
            ->when($nif !== '', fn ($q) => $q->where('supplier_nif', $nif))
            ->when($nif === '' && $fornecedor !== '', fn ($q) => $q->where('fornecedor', $fornecedor))
            ->first();
    }

    /**
     * Grava o documento: totais, linhas e marca, tudo numa transaccao.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: AccountingDocument, 1: array<int, string>}
     */
    private function registar(array $data, User $utilizador): array
    {
        return DB::transaction(function () use ($data, $utilizador) {
            $avisos = [];

            [$linhas, $baseSemIva, $ivaCalculado] = $this->linhas($data['linhas'] ?? []);

            $totalCalculado = round($baseSemIva + $ivaCalculado, 2);
            $valor = isset($data['valor']) ? round((float) $data['valor'], 2) : $totalCalculado;
            $iva = isset($data['iva']) ? round((float) $data['iva'], 2) : round($ivaCalculado, 2);

            if (isset($data['valor']) && $linhas !== [] && abs($valor - $totalCalculado) > 0.02) {
                $avisos[] = sprintf(
                    'o total indicado (%.2f) nao bate com a soma das linhas com IVA (%.2f); foi guardado o total indicado.',
                    $valor,
                    $totalCalculado
                );
            }

            if ($linhas === []) {
                $avisos[] = 'fatura sem linhas de produtos; ficou so com o total.';
            }

            $nif = trim((string) ($data['nif'] ?? '')) ?: null;
            $fornecedor = trim((string) ($data['fornecedor'] ?? '')) ?: null;

            // O QR traz o NIF mas nao o nome: se ele nao vier no pedido,
            // reaproveita-se o que ja foi usado para este NIF.
            if ($fornecedor === null && $nif !== null) {
                $fornecedor = AccountingDocument::fornecedorPorNif($nif);

                if ($fornecedor !== null) {
                    $avisos[] = "fornecedor assumido pelo NIF: {$fornecedor}.";
                }
            }

            $marca = $this->marca($data['marca'] ?? null, $avisos);

            $documento = new AccountingDocument();

            $documento->fill([
                'tipo' => $data['tipo'] ?? 'fatura',
                // O `title` e onde este projecto guarda a finalidade.
                'title' => $data['finalidade'] ?? 'outro',
                'estado' => $data['estado'] ?? 'pendente',
                'invoice_number' => $data['numero_fatura'] ?? null,
                'fornecedor' => $fornecedor,
                'supplier_nif' => $nif,
                'atcud' => $data['atcud'] ?? null,
                'date' => $data['data'],
                'amount_cents' => (int) round($valor * 100),
                'iva_cents' => (int) round($iva * 100),
                'currency' => $data['moeda'] ?? 'EUR',
                'category' => $data['categoria'] ?? 'fornecedores',
                'brand_id' => $marca,
                'products' => $linhas,
                'notes' => $data['notas'] ?? null,
                'origem' => 'api',
                'importado_contabilidade' => false,
            ]);

            $documento->save();

            return [$documento, $avisos];
        });
    }

    /**
     * As linhas no formato em que este projecto as guarda (`products`), e os
     * totais que delas saem.
     *
     * As chaves sao as do extractor de faturas em papel — description,
     * quantity, unitPrice, vatRate, lineTotal — para o ecra de revisao e o
     * portal do contabilista lerem as duas vias sem saber de onde vieram.
     * `total_linha`, quando vem, e o total da linha SEM IVA, como na fatura.
     *
     * @param  array<int, array<string, mixed>>  $linhasDadas
     * @return array{0: array<int, array<string, mixed>>, 1: float, 2: float}
     */
    private function linhas(array $linhasDadas): array
    {
        $linhas = [];
        $baseSemIva = 0.0;
        $iva = 0.0;

        foreach ($linhasDadas as $linha) {
            $quantidade = (float) $linha['quantidade'];
            $preco = (float) $linha['preco_unitario'];
            $desconto = (float) ($linha['desconto_percentagem'] ?? 0);
            $taxa = (float) ($linha['iva_percentagem'] ?? 0);

            $totalLinha = isset($linha['total_linha'])
                ? round((float) $linha['total_linha'], 2)
                : round($quantidade * $preco * (1 - $desconto / 100), 2);

            $baseSemIva += $totalLinha;
            $iva += $totalLinha * $taxa / 100;

            $linhas[] = [
                'description' => (string) $linha['descricao'],
                'quantity' => $quantidade,
                'unitPrice' => round($preco * (1 - $desconto / 100), 4),
                'vatRate' => $taxa,
                'lineTotal' => $totalLinha,
                'confidence' => 1.0,
            ];
        }

        return [$linhas, round($baseSemIva, 2), round($iva, 2)];
    }

    /**
     * A marca do documento: id, nome, ou a de omissao da configuracao do
     * importador de email — para as duas vias caírem na mesma quando ninguem diz.
     *
     * @param  array<int, string>  $avisos
     */
    private function marca(mixed $referencia, array &$avisos): ?int
    {
        if (is_array($referencia)) {
            $referencia = $referencia['id'] ?? $referencia['nome'] ?? null;
        }

        if (is_numeric($referencia)) {
            $marca = Brand::query()->find((int) $referencia);

            if ($marca === null) {
                $avisos[] = "marca #{$referencia} nao existe; o documento ficou sem marca.";

                return null;
            }

            return $marca->id;
        }

        if (filled($referencia)) {
            $encontradas = Brand::query()->where('name', 'like', trim((string) $referencia).'%')->limit(2)->get();

            if ($encontradas->count() === 1) {
                return $encontradas->first()->id;
            }

            $avisos[] = $encontradas->isEmpty()
                ? "marca \"{$referencia}\" nao encontrada; o documento ficou sem marca."
                : "marca \"{$referencia}\" e ambigua ({$encontradas->count()} candidatas); o documento ficou sem marca.";

            return null;
        }

        $porOmissao = config('faturas_email.default_brand_id');

        return is_numeric($porOmissao) ? (int) $porOmissao : null;
    }

    /**
     * Uma linha do resultado do lote.
     *
     * @param  array<string, mixed>|null  $dados
     * @param  array<int, string>  $avisos
     * @param  array<string, mixed>  $erros
     * @return array<string, mixed>
     */
    private function resultadoLote(
        int $indice,
        ?string $referencia,
        string $estado,
        ?array $dados = null,
        array $avisos = [],
        array $erros = []
    ): array {
        return [
            'indice' => $indice,
            'numero_fatura' => $referencia,
            'estado' => $estado,
            'sucesso' => $estado !== 'erro',
            'documento_id' => $dados['documento']['id'] ?? null,
            'dados' => $dados,
            'avisos' => $avisos,
            'erros' => $erros,
        ];
    }

    /** @return array<string, mixed> */
    private function formatar(AccountingDocument $documento): array
    {
        $documento->loadMissing('brand');

        return [
            'documento' => [
                'id' => $documento->id,
                'tipo' => $documento->tipo,
                'estado' => $documento->estado,
                'numero_fatura' => $documento->invoice_number,
                'fornecedor' => $documento->fornecedor,
                'nif' => $documento->supplier_nif,
                'atcud' => $documento->atcud,
                'data' => $documento->date?->toDateString(),
                'valor' => $documento->amount,
                'iva' => $documento->iva,
                'moeda' => $documento->currency,
                'categoria' => $documento->category,
                'finalidade' => $documento->title,
                'origem' => $documento->origem,
                'marca' => $documento->brand === null ? null : [
                    'id' => $documento->brand->id,
                    'nome' => $documento->brand->full_name,
                ],
                'visivel_ao_contabilista' => $documento->estado !== 'por_rever',
                'ficheiro_url' => $documento->file_path
                    ? Storage::disk('public')->url($documento->file_path)
                    : (($documento->image_paths[0] ?? null)
                        ? Storage::disk('public')->url($documento->image_paths[0])
                        : null),
                'linhas' => $documento->products ?? [],
            ],
        ];
    }
}
