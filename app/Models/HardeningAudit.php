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
            'pendente'   => 'A correr',
            'incompleta' => 'Incompleta',
            'ok'         => 'Tudo bem',
            'avisos'   => 'Com avisos',
            'falhas'   => 'Por corrigir',
            'erro'     => 'Erro',
        ];
    }

    public static function estadoCores(): array
    {
        return [
            'pendente'   => 'gray',
            'incompleta' => 'warning',
            'ok'         => 'success',
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

    /** Há alguma correcção na fila? Enquanto houver, a página vai-se refrescando. */
    public function temCorreccoesACorrer(): bool
    {
        foreach ($this->resultados ?? [] as $r) {
            if (! empty($r['a_correr'])) {
                return true;
            }
        }

        return false;
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

        $this->update(['estado' => self::estadoPara($resultados)]);
    }

    /**
     * O estado geral a partir das linhas. Falhas primeiro; depois "incompleta"
     * quando alguma verificação ficou por ler (resposta cortada) — senão uma
     * máquina meio lida passava por "Tudo bem".
     */
    public static function estadoPara(array $resultados): string
    {
        $tem = fn (callable $f) => count(array_filter($resultados, $f)) > 0;

        return match (true) {
            $tem(fn ($r) => ($r['estado'] ?? '') === 'falha') => 'falhas',
            $tem(fn ($r) => ! empty($r['cortada']))           => 'incompleta',
            $tem(fn ($r) => ($r['estado'] ?? '') === 'aviso') => 'avisos',
            default                                           => 'ok',
        };
    }
}
