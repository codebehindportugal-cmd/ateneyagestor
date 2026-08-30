<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um pedido de trabalho ao Claude sobre uma tarefa de projecto.
 *
 * O painel so cria o registo (queued) — quem o executa e o comando `claude:work`,
 * que corre na maquina onde os repositorios estao. O site nunca chama o worker,
 * tal como o site nunca chama o Pi dos backups: e sempre o worker que vem buscar.
 */
class ClaudeRun extends Model
{
    protected $fillable = [
        'project_task_id',
        'status',
        'mode',
        'follow_up',
        'prompt',
        'result',
        'error',
        'session_id',
        'cost_usd',
        'duration_ms',
        'requested_by',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'cost_usd'    => 'decimal:4',
            'duration_ms' => 'integer',
            'started_at'  => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public static function statusOptions(): array
    {
        return [
            'queued'  => 'Na fila',
            'running' => 'A correr',
            'done'    => 'Respondido',
            'failed'  => 'Falhou',
        ];
    }

    public static function statusColor(?string $status): string
    {
        return match ($status) {
            'done'    => 'success',
            'running' => 'warning',
            'failed'  => 'danger',
            default   => 'gray',
        };
    }

    public static function modeOptions(): array
    {
        return [
            'diagnose' => 'Diagnosticar e propor',
            'continue' => 'Continuar a conversa',
            'apply'    => 'Continuar e alterar ficheiros',
        ];
    }

    /** Este pedido pode mexer em ficheiros? */
    public function writes(): bool
    {
        return $this->mode === 'apply';
    }

    /** E uma continuacao, e nao um diagnostico de raiz? */
    public function isFollowUp(): bool
    {
        return filled($this->follow_up);
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? (string) $this->status;
    }

    /** Ainda nao terminou — nao deve deixar pedir outra vez para a mesma tarefa. */
    public function isPending(): bool
    {
        return in_array($this->status, ['queued', 'running'], true);
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    /**
     * Um run que ficou "running" ha demasiado tempo e um worker que morreu a meio.
     * Sem isto a tarefa ficava para sempre sem poder ser pedida de novo.
     */
    public function isStale(): bool
    {
        return $this->status === 'running'
            && $this->started_at !== null
            && $this->started_at->lt(now()->subMinutes(30));
    }

    public function durationLabel(): ?string
    {
        if ($this->duration_ms === null) {
            return null;
        }

        $seconds = $this->duration_ms / 1000;

        return $seconds < 60
            ? number_format($seconds, 0) . ' s'
            : number_format($seconds / 60, 1, ',', '.') . ' min';
    }

    public function costLabel(): ?string
    {
        return $this->cost_usd === null ? null : '$' . number_format((float) $this->cost_usd, 3, ',', '.');
    }
}
