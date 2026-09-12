<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HardeningAudit extends Model
{
    protected $fillable = [
        'server_id',
        'estado',
        'lancada_por',
        'total',
        'falhas',
        'avisos',
        'resultados',
        'erro',
        'comecou_em',
        'acabou_em',
    ];

    protected function casts(): array
    {
        return [
            'resultados' => 'array',
            'comecou_em' => 'datetime',
            'acabou_em'  => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public static function estadoLabels(): array
    {
        return [
            'pendente' => 'A correr',
            'ok'       => 'Tudo bem',
            'avisos'   => 'Com avisos',
            'falhas'   => 'Por corrigir',
            'erro'     => 'Erro',
        ];
    }

    public static function estadoCores(): array
    {
        return [
            'pendente' => 'gray',
            'ok'       => 'success',
            'avisos'   => 'warning',
            'falhas'   => 'danger',
            'erro'     => 'danger',
        ];
    }

    public function estadoLabel(): string
    {
        return self::estadoLabels()[$this->estado] ?? $this->estado;
    }

    /** As verificações por estado, para a página de detalhe. */
    public function porEstado(string $estado): array
    {
        return array_values(array_filter(
            $this->resultados ?? [],
            fn (array $r) => ($r['estado'] ?? '') === $estado,
        ));
    }

    public function resultado(string $chave): ?array
    {
        foreach ($this->resultados ?? [] as $r) {
            if (($r['chave'] ?? null) === $chave) {
                return $r;
            }
        }

        return null;
    }

    /** Substitui uma linha de resultado (depois de corrigir e voltar a verificar). */
    public function substituirResultado(string $chave, array $novo): void
    {
        $resultados = array_map(
            fn (array $r) => ($r['chave'] ?? null) === $chave ? $novo : $r,
            $this->resultados ?? [],
        );

        $this->update([
            'resultados' => $resultados,
            'falhas'     => count(array_filter($resultados, fn ($r) => ($r['estado'] ?? '') === 'falha')),
            'avisos'     => count(array_filter($resultados, fn ($r) => ($r['estado'] ?? '') === 'aviso')),
        ]);

        $this->update(['estado' => match (true) {
            $this->falhas > 0 => 'falhas',
            $this->avisos > 0 => 'avisos',
            default           => 'ok',
        }]);
    }
}
