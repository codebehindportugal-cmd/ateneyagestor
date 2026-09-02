<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma linha do histórico de uma tarefa: quem fez o quê, quando, e — no caso de
 * uma alteração — o que estava antes e o que ficou depois.
 *
 * É escrita pelo próprio ProjectTask (ver ProjectTask::booted) e pelas acções
 * do painel. Nunca se edita nem se apaga uma linha destas: é o rasto.
 */
class TaskActivity extends Model
{
    protected $fillable = [
        'project_task_id',
        'user_id',
        'type',
        'body',
        'changes',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function typeOptions(): array
    {
        return [
            'created'   => 'Criou a tarefa',
            'assigned'  => 'Atribuiu',
            'status'    => 'Mudou o estado',
            'updated'   => 'Alterou',
            'comment'   => 'Comentou',
            'completed' => 'Concluiu',
            'reopened'  => 'Reabriu',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? (string) $this->type;
    }

    public static function typeColor(?string $type): string
    {
        return match ($type) {
            'completed' => 'success',
            'reopened'  => 'warning',
            'comment'   => 'info',
            'assigned'  => 'primary',
            default     => 'gray',
        };
    }

    public static function typeIcon(?string $type): string
    {
        return match ($type) {
            'created'   => 'heroicon-o-plus-circle',
            'assigned'  => 'heroicon-o-user-plus',
            'status'    => 'heroicon-o-arrow-path',
            'comment'   => 'heroicon-o-chat-bubble-left-ellipsis',
            'completed' => 'heroicon-o-check-circle',
            'reopened'  => 'heroicon-o-arrow-uturn-left',
            default     => 'heroicon-o-pencil-square',
        };
    }

    /** Nome de quem fez isto, mesmo que a conta já tenha sido apagada. */
    public function actorName(): string
    {
        return $this->user?->name ?? 'Sistema';
    }
}
