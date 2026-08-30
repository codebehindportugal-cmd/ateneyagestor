<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Uma data concreta de uma rotina: a segunda-feira desta semana, a renda de
 * Setembro. É isto que se marca como feito ou pago, e é por existir uma linha
 * por ocorrência que se consegue ver o que ficou por fazer.
 */
class RoutineOccurrence extends Model
{
    protected $fillable = [
        'routine_id', 'due_date', 'status', 'amount_cents',
        'completed_at', 'completed_by', 'accounting_document_id', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'due_date'     => 'date',
            'completed_at' => 'datetime',
            'amount_cents' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $o) {
            if (in_array($o->status, ['feito', 'saltado'], true)) {
                $o->completed_at = $o->completed_at ?: now();
                $o->completed_by = $o->completed_by ?: Auth::id();
            } else {
                $o->completed_at = null;
                $o->completed_by = null;
            }
        });
    }

    public static function estados(): array
    {
        return [
            'pendente' => 'Por fazer',
            'feito'    => 'Feito',
            'saltado'  => 'Saltado',
        ];
    }

    public static function estadoColor(?string $estado): string
    {
        return match ($estado) {
            'feito'   => 'success',
            'saltado' => 'gray',
            default   => 'warning',
        };
    }

    public function routine(): BelongsTo
    {
        return $this->belongsTo(Routine::class);
    }

    public function accountingDocument(): BelongsTo
    {
        return $this->belongsTo(AccountingDocument::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function estaPendente(): bool
    {
        return $this->status === 'pendente';
    }

    /** Passou a data e continua por fazer. */
    public function estaAtrasada(): bool
    {
        return $this->estaPendente() && $this->due_date?->isPast();
    }

    public function marcarFeito(): void
    {
        $this->status = 'feito';
        $this->save();
    }

    public function reabrir(): void
    {
        $this->status = 'pendente';
        $this->save();
    }
}
