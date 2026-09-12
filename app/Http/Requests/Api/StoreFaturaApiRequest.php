<?php

namespace App\Http\Requests\Api;

use App\Models\AccountingDocument;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Uma fatura de fornecedor a entrar pela API (documento de contabilidade).
 *
 * As chaves sao as mesmas do agro.codebehind.pt onde o significado e o mesmo
 * (numero_fatura, fornecedor, data, valor, categoria, notas, linhas) para a
 * mesma leitura servir os dois sitios; o que e proprio da contabilidade — NIF,
 * ATCUD, tipo de documento, finalidade, marca, estado — e so daqui.
 */
class StoreFaturaApiRequest extends FormRequest
{
    public const TAXAS_IVA = [0, 6, 13, 23];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::regrasFatura();
    }

    /**
     * As regras de uma fatura, sem prefixo.
     *
     * Publicas e static porque o lote valida cada fatura por si: uma mal lida
     * nao pode levar atras as outras do mesmo envio.
     *
     * @return array<string, mixed>
     */
    public static function regrasFatura(): array
    {
        return [
            'numero_fatura' => ['nullable', 'string', 'max:255'],
            'tipo' => ['nullable', 'string', Rule::in(array_keys(AccountingDocument::tipos()))],
            'fornecedor' => ['nullable', 'string', 'max:255'],
            // O NIF do emitente: campo A do QR da AT, a fonte mais fiavel que a
            // fatura tem. E com ele que o fornecedor se acerta a si proprio.
            'nif' => ['nullable', 'string', 'max:20'],
            'atcud' => ['nullable', 'string', 'max:100'],
            'data' => ['required', 'date'],
            // Total a pagar, com IVA. Sem ele soma-se pelas linhas.
            'valor' => ['nullable', 'numeric', 'min:0'],
            'iva' => ['nullable', 'numeric', 'min:0'],
            'moeda' => ['nullable', 'string', 'size:3'],
            'categoria' => ['nullable', 'string', Rule::in(array_keys(AccountingDocument::categories()))],
            'finalidade' => ['nullable', 'string', Rule::in(array_keys(AccountingDocument::finalidades()))],
            'marca' => ['nullable'],
            'estado' => ['nullable', 'string', Rule::in(array_keys(AccountingDocument::estados()))],
            'notas' => ['nullable', 'string'],

            'linhas' => ['nullable', 'array'],
            'linhas.*.descricao' => ['required_with:linhas', 'string', 'max:255'],
            'linhas.*.quantidade' => ['required_with:linhas', 'numeric', 'gt:0'],
            'linhas.*.preco_unitario' => ['required_with:linhas', 'numeric', 'min:0'],
            'linhas.*.desconto_percentagem' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'linhas.*.iva_percentagem' => ['nullable', 'numeric', Rule::in(self::TAXAS_IVA)],
            'linhas.*.total_linha' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return self::mensagensFatura();
    }

    /** @return array<string, string> */
    public static function mensagensFatura(): array
    {
        $taxas = implode(', ', self::TAXAS_IVA);

        return [
            'linhas.*.iva_percentagem.in' => "Taxa de IVA invalida. Valores aceites: {$taxas}.",
            'categoria.in' => 'Categoria invalida. Ver AccountingDocument::categories().',
            'finalidade.in' => 'Finalidade invalida. Ver AccountingDocument::finalidades().',
            'estado.in' => 'Estado invalido. Ver AccountingDocument::estados().',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'sucesso' => false,
            'dados' => null,
            'avisos' => [],
            'erros' => $validator->errors()->toArray(),
        ], 422));
    }
}
