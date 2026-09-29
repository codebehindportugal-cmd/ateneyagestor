<?php

namespace App\Services\Moloni;

use App\Models\MoloniDocumento;
use App\Models\Setting;

/**
 * Traz do Moloni as faturas de venda de um ano para a moloni_documentos.
 *
 * Reescreve o ano inteiro: o que veio e' gravado, e o que estava cá desse ano
 * e ja nao veio (anulado, passado a rascunho) sai. So' se apaga depois de a
 * leitura ter chegado ao fim — uma falha a meio deixa a copia como estava em
 * vez de a deixar pela metade.
 */
class SincronizadorMoloni
{
    public const CHAVE_ULTIMA = 'moloni.ultima_sincronizacao';

    public function __construct(private readonly MoloniClient $cliente)
    {
    }

    /** @return array{ano:int,recebidos:int,gravados:int,removidos:int,empresa:int} */
    public function ano(int $ano): array
    {
        $empresa = $this->cliente->empresaId();
        $agora = now();
        $vistos = [];
        $gravados = 0;
        $recebidos = 0;

        foreach ($this->cliente->documentosDoAno($empresa, $ano) as $documento) {
            $recebidos++;
            $linha = NormalizadorDocumento::linha($documento, $empresa);

            if ($linha === null) {
                continue;
            }

            MoloniDocumento::updateOrCreate(
                ['moloni_document_id' => $linha['moloni_document_id']],
                $linha + ['sincronizado_em' => $agora],
            );

            $vistos[] = $linha['moloni_document_id'];
            $gravados++;
        }

        $removidos = MoloniDocumento::query()
            ->where('company_id', $empresa)
            ->where('ano', $ano)
            ->when($vistos !== [], fn ($q) => $q->whereNotIn('moloni_document_id', $vistos))
            ->delete();

        Setting::set(self::CHAVE_ULTIMA, $agora->toIso8601String());

        return compact('ano', 'recebidos', 'gravados', 'removidos', 'empresa');
    }
}
