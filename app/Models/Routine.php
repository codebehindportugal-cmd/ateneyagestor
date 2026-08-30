<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uma coisa que se repete: "backup semanal", "renda dia 5", "IVA trimestral".
 * Guarda a REGRA. As datas concretas vivem em RoutineOccurrence, geradas por
 * routines:generate — é isso que permite marcar cada semana como feita e ver
 * o que ficou por fazer.
 */
class Routine extends Model
{
    protected $fillable = [
        'nome', 'tipo', 'periodicidade',
        'dia_semana', 'dia_mes', 'mes',
        'amount_cents', 'fornecedor', 'brand_id',
        'starts_on', 'ends_on', 'is_active', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'dia_semana'   => 'integer',
            'dia_mes'      => 'integer',
            'mes'          => 'integer',
            'amount_cents' => 'integer',
            'starts_on'    => 'date',
            'ends_on'      => 'date',
            'is_active'    => 'boolean',
        ];
    }

    public static function tipos(): array
    {
        return [
            'tarefa'    => 'Tarefa',
            'pagamento' => 'Pagamento',
        ];
    }

    public static function periodicidades(): array
    {
        return [
            'semanal'    => 'Semanal',
            'quinzenal'  => 'De 2 em 2 semanas',
            'mensal'     => 'Mensal',
            'trimestral' => 'Trimestral',
            'semestral'  => 'Semestral',
            'anual'      => 'Anual',
        ];
    }

    public static function diasDaSemana(): array
    {
        return [
            1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta',
            5 => 'Sexta', 6 => 'Sábado', 7 => 'Domingo',
        ];
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(RoutineOccurrence::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function isPagamento(): bool
    {
        return $this->tipo === 'pagamento';
    }

    /** De quantos em quantos meses, para as periodicidades mensais. */
    private function passoEmMeses(): ?int
    {
        return match ($this->periodicidade) {
            'mensal'     => 1,
            'trimestral' => 3,
            'semestral'  => 6,
            'anual'      => 12,
            default      => null,
        };
    }

    /**
     * As datas em que esta rotina cai dentro do intervalo pedido.
     *
     * Devolve sempre datas reais: uma rotina de dia 31 cai a 28 ou 29 em
     * Fevereiro, em vez de escorregar para Março como faria um addMonth()
     * ingénuo — que é o erro classico neste tipo de codigo.
     */
    public function datasNoIntervalo(CarbonImmutable $de, CarbonImmutable $ate): array
    {
        $de = $de->startOfDay();
        $ate = $ate->startOfDay();

        if ($this->starts_on) {
            $de = $de->max(CarbonImmutable::parse($this->starts_on)->startOfDay());
        }

        if ($this->ends_on) {
            $ate = $ate->min(CarbonImmutable::parse($this->ends_on)->startOfDay());
        }

        if ($de->greaterThan($ate)) {
            return [];
        }

        return in_array($this->periodicidade, ['semanal', 'quinzenal'], true)
            ? $this->datasSemanais($de, $ate)
            : $this->datasMensais($de, $ate);
    }

    private function datasSemanais(CarbonImmutable $de, CarbonImmutable $ate): array
    {
        $diaSemana = $this->dia_semana ?: 1;

        // Âncora das quinzenais: o starts_on, ou a época, para a contagem das
        // semanas ser estável e não mudar consoante quando se gera.
        $ancora = CarbonImmutable::parse($this->starts_on ?: '2026-01-05')->startOfDay();
        $ancora = $ancora->subDays(($ancora->dayOfWeekIso - $diaSemana + 7) % 7);

        $datas = [];
        $data = $de->addDays(($diaSemana - $de->dayOfWeekIso + 7) % 7);

        while ($data->lessThanOrEqualTo($ate)) {
            if ($this->periodicidade === 'semanal'
                || ((int) round($ancora->diffInDays($data) / 7)) % 2 === 0) {
                $datas[] = $data->toDateString();
            }
            $data = $data->addWeek();
        }

        return $datas;
    }

    private function datasMensais(CarbonImmutable $de, CarbonImmutable $ate): array
    {
        $passo = $this->passoEmMeses() ?? 1;
        $dia = $this->dia_mes ?: 1;
        $datas = [];

        $mes = $de->startOfMonth();

        while ($mes->lessThanOrEqualTo($ate)) {
            $cai = true;

            if ($this->periodicidade === 'anual') {
                $cai = ((int) $mes->month) === ((int) ($this->mes ?: 1));
            } elseif ($passo > 1) {
                // Alinhado ao mês de arranque, para um trimestral começado em
                // Fevereiro cair em Fev/Mai/Ago/Nov e não em Jan/Abr/Jul/Out.
                $base = $this->starts_on
                    ? CarbonImmutable::parse($this->starts_on)
                    : $mes->setMonth((int) ($this->mes ?: 1));
                $delta = (($mes->year - $base->year) * 12) + ($mes->month - $base->month);
                $cai = $delta >= 0 && $delta % $passo === 0;
            }

            if ($cai) {
                // Encurtar ao último dia do mês: dia 31 em Fevereiro é dia 28/29.
                $data = $mes->setDay(min($dia, $mes->daysInMonth));

                if ($data->betweenIncluded($de, $ate)) {
                    $datas[] = $data->toDateString();
                }
            }

            $mes = $mes->addMonth();
        }

        return $datas;
    }
}
