<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ProjectTask extends Model
{
    /**
     * Estados em que uma tarefa nunca conta como "em atraso": ou já terminou,
     * ou a bola está do lado do cliente e o prazo não é falha nossa.
     * Vive aqui e não no filtro do Filament para não haver duas versões da
     * mesma regra a divergirem com o tempo.
     */
    public const NOT_OVERDUE_STATUSES = ['done', 'cancelled', 'waiting_client'];

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'status',
        'position',
        'due_date',
        'hours',
        'completed_at',
        'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date'     => 'date',
            'completed_at' => 'datetime',
            'hours'        => 'decimal:2',
            'position'     => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Regista automaticamente quando (e por quem) a tarefa foi concluída,
        // e limpa esse registo se a tarefa for reaberta.
        static::saving(function (self $task) {
            if ($task->status === 'done') {
                if (empty($task->completed_at)) {
                    $task->completed_at = now();
                    $task->completed_by = $task->completed_by ?: Auth::id();
                }
            } else {
                $task->completed_at = null;
                $task->completed_by = null;
            }

            if (empty($task->position)) {
                $task->position = (int) static::where('project_id', $task->project_id)->max('position') + 1;
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public static function statusOptions(): array
    {
        return [
            'pending'        => 'Por fazer',
            'in_progress'    => 'Em curso',
            'waiting_client' => 'A aguardar cliente',
            'done'           => 'Feito',
            'cancelled'      => 'Cancelado',
        ];
    }

    public static function statusColor(?string $status): string
    {
        return match ($status) {
            'done'           => 'success',
            'in_progress'    => 'warning',
            'waiting_client' => 'info',
            'cancelled'      => 'danger',
            default          => 'gray',
        };
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? (string) $this->status;
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && ! in_array($this->status, self::NOT_OVERDUE_STATUSES, true)
            && $this->due_date->isPast();
    }

    public function isWaitingOnClient(): bool
    {
        return $this->status === 'waiting_client';
    }

    /** Marca que ficámos à espera do cliente. */
    public function markWaitingOnClient(): void
    {
        $this->status = 'waiting_client';
        $this->save();
    }

    /** O cliente respondeu — a tarefa volta para as nossas mãos. */
    public function resumeFromClient(): void
    {
        $this->status = 'in_progress';
        $this->save();
    }

    public function markDone(): void
    {
        $this->status       = 'done';
        $this->completed_at = now();
        $this->completed_by = Auth::id();
        $this->save();
    }

    public function reopen(): void
    {
        $this->status = 'pending';
        $this->save();
    }
}
