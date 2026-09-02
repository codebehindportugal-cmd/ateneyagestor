<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    /**
     * Campos cuja alteração fica registada no histórico. O resto (position,
     * por exemplo, que muda só de se arrastar uma linha) não interessa a
     * ninguém e só encheria o rasto de ruído.
     */
    public const TRACKED_FIELDS = [
        'title'            => 'Tarefa',
        'description'      => 'Notas',
        'status'           => 'Estado',
        'due_date'         => 'Prazo',
        'hours'            => 'Horas',
        'estimated_hours'  => 'Estimativa',
        'assigned_user_id' => 'Responsável',
    ];

    protected $fillable = [
        'project_id',
        'assigned_user_id',
        'title',
        'description',
        'status',
        'position',
        'due_date',
        'hours',
        'estimated_hours',
        'completed_at',
        'completed_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date'     => 'date',
            'completed_at' => 'datetime',
            'hours'           => 'decimal:2',
            'estimated_hours' => 'decimal:2',
            'position'     => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $task) {
            if (empty($task->created_by)) {
                $task->created_by = Auth::id();
            }

            // Um estagiário que aponte uma tarefa fica com ela: caso contrário
            // criava-a e ela desaparecia-lhe da lista no instante seguinte.
            if (empty($task->assigned_user_id) && Auth::user()?->isEstagiario()) {
                $task->assigned_user_id = Auth::id();
            }
        });

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

        static::created(function (self $task) {
            $task->logActivity('created');
        });

        static::updated(function (self $task) {
            $task->recordChanges();
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // -----------------------------------------------------------------
    // Âmbito
    // -----------------------------------------------------------------

    /**
     * As tarefas que estão no balcão: sem dono, por fazer, e num projecto que
     * foi aberto a estagiários. A última condição é o que impede que as
     * tarefas onde o André regista o estado dos projectos apareçam como
     * trabalho por distribuir.
     */
    public function scopePorEscolher(Builder $query): Builder
    {
        return $query
            ->whereNull('assigned_user_id')
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereHas('project', fn (Builder $projecto) => $projecto->where('open_to_interns', true));
    }

    /**
     * O que esta pessoa pode ver: um administrador vê tudo, os restantes vêem
     * o que é seu mais o que está no balcão.
     */
    public function scopeVisivelPara(Builder $query, ?User $user): Builder
    {
        if ($user === null || $user->isAdmin()) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where('assigned_user_id', $user->id)
            ->orWhere(fn (Builder $livre) => $livre->porEscolher()));
    }

    /** Esta tarefa está no balcão, disponível para quem a quiser? */
    public function podeSerEscolhida(): bool
    {
        return $this->assigned_user_id === null
            && ! in_array($this->status, ['done', 'cancelled'], true)
            && $this->project?->open_to_interns === true;
    }

    /** Quem está encarregue da tarefa. Nulo = ainda por distribuir. */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /** O histórico da tarefa, do mais antigo para o mais recente. */
    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->oldest();
    }

    public function claudeRuns(): HasMany
    {
        return $this->hasMany(ClaudeRun::class);
    }

    /**
     * O ultimo pedido feito ao Claude sobre esta tarefa. E uma relacao (e nao um
     * metodo que faz query) para a listagem poder carregar tudo de uma vez.
     */
    public function lastClaudeRun(): HasOne
    {
        return $this->hasOne(ClaudeRun::class)->latestOfMany();
    }

    // -----------------------------------------------------------------
    // Histórico
    // -----------------------------------------------------------------

    /** Escreve uma linha no histórico desta tarefa. */
    public function logActivity(string $type, ?string $body = null, ?array $changes = null): TaskActivity
    {
        return $this->activities()->create([
            'user_id' => Auth::id(),
            'type'    => $type,
            'body'    => $body,
            'changes' => $changes,
        ]);
    }

    /**
     * Traduz o que acabou de mudar numa (ou mais) linhas de histórico legíveis.
     * Corre no evento `updated`, quando o Eloquent já sabe o antes e o depois.
     */
    protected function recordChanges(): void
    {
        $changed = array_intersect_key($this->getChanges(), self::TRACKED_FIELDS);

        if ($changed === []) {
            return;
        }

        // O estado tem linha própria: é o que se quer ver de relance no rasto.
        if (array_key_exists('status', $changed)) {
            $antes  = $this->getOriginal('status');
            $depois = $this->status;

            $tipo = match (true) {
                $depois === 'done' => 'completed',
                $antes === 'done'  => 'reopened',
                default            => 'status',
            };

            $this->logActivity($tipo, null, [
                'campo' => 'Estado',
                'antes' => self::statusOptions()[$antes] ?? $antes,
                'depois' => self::statusOptions()[$depois] ?? $depois,
            ]);

            unset($changed['status']);
        }

        if (array_key_exists('assigned_user_id', $changed)) {
            $antes  = $this->getOriginal('assigned_user_id');
            $depois = $this->assigned_user_id;

            $this->logActivity('assigned', null, [
                'campo'  => 'Responsável',
                'antes'  => $antes ? (User::find($antes)?->name ?? '#' . $antes) : 'por atribuir',
                'depois' => $depois ? ($this->assignedUser?->name ?? '#' . $depois) : 'por atribuir',
            ]);

            unset($changed['assigned_user_id']);
        }

        foreach ($changed as $campo => $depois) {
            $this->logActivity('updated', null, [
                'campo'  => self::TRACKED_FIELDS[$campo],
                'antes'  => self::describeValue($campo, $this->getOriginal($campo)),
                'depois' => self::describeValue($campo, $depois),
            ]);
        }
    }

    /** Põe um valor de campo em texto curto para o histórico. */
    protected static function describeValue(string $campo, mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        return match ($campo) {
            'due_date'    => substr((string) $valor, 0, 10),
            'hours', 'estimated_hours' => number_format((float) $valor, 2, ',', '.') . ' h',
            'description' => \Illuminate\Support\Str::limit((string) $valor, 120),
            default       => (string) $valor,
        };
    }

    // -----------------------------------------------------------------
    // Estados
    // -----------------------------------------------------------------

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

    /** "6 h" em vez de "6.00". Se não houver nada, devolve null. */
    public static function formatarHoras(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $numero = (float) $valor;
        $casas  = fmod($numero, 1.0) === 0.0 ? 0 : 2;

        return number_format($numero, $casas, ',', '.') . ' h';
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
