<?php

namespace App\Models;

use App\Services\Contabilidade\Resultados;
use Illuminate\Database\Eloquent\Model;

class MoloniDocumento extends Model
{
    protected $table = 'moloni_documentos';

    protected $fillable = [
        'moloni_document_id',
        'company_id',
        'tipo',
        'document_set_id',
        'serie',
        'numero',
        'data',
        'ano',
        'mes',
        'cliente',
        'cliente_nif',
        'base_cents',
        'iva_cents',
        'total_cents',
        'estado',
        'sincronizado_em',
    ];

    protected function casts(): array
    {
        return [
            'data'            => 'date',
            'ano'             => 'integer',
            'mes'             => 'integer',
            'numero'          => 'integer',
            'base_cents'      => 'integer',
            'iva_cents'       => 'integer',
            'total_cents'     => 'integer',
            'estado'          => 'integer',
            'sincronizado_em' => 'datetime',
        ];
    }

    /**
     * Mapa serie -> marca, guardado em settings como JSON {"<document_set_id>": <brand_id>}.
     *
     * @return array<int,int>
     */
    public static function mapaSeries(): array
    {
        $json = Setting::get(Resultados::CHAVE_MAPA_SERIES);
        $mapa = is_string($json) ? json_decode($json, true) : null;

        if (! is_array($mapa)) {
            return [];
        }

        $limpo = [];
        foreach ($mapa as $serie => $marca) {
            if ((int) $serie > 0 && (int) $marca > 0) {
                $limpo[(int) $serie] = (int) $marca;
            }
        }

        return $limpo;
    }

    /** @param array<int,int|null> $mapa */
    public static function guardarMapaSeries(array $mapa): void
    {
        $limpo = array_filter(
            array_map(fn ($v) => $v ? (int) $v : null, $mapa),
            fn ($v) => $v !== null && $v > 0,
        );

        Setting::set(Resultados::CHAVE_MAPA_SERIES, json_encode((object) $limpo));
    }

    /**
     * Series que ja apareceram nas faturas sincronizadas: [document_set_id => nome].
     *
     * @return array<int,string>
     */
    public static function seriesConhecidas(): array
    {
        return static::query()
            ->whereNotNull('document_set_id')
            ->select('document_set_id', 'serie')
            ->distinct()
            ->orderBy('serie')
            ->get()
            ->mapWithKeys(fn ($d) => [(int) $d->document_set_id => (string) ($d->serie ?: "Série {$d->document_set_id}")])
            ->all();
    }
}
