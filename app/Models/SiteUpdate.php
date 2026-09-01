<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma actualizacao de WordPress: o que se actualizou, como ficou o site
 * depois, e o que teve de ser repost.
 *
 * O painel so cria o registo em "queued" — nunca liga a lado nenhum. Quem
 * executa e o agente dos backups, que ja tem SSH para todas as VPS e vem
 * buscar o trabalho por HTTP. E o mesmo sentido de conversa do resto do
 * painel: de fora para dentro.
 */
class SiteUpdate extends Model
{
    protected $fillable = [
        'site_id',
        'requested_by',
        'status',
        'mode',
        'agendado_para',
        'snapshot_path',
        'antes',
        'depois',
        'itens',
        'total_actualizados',
        'total_repostos',
        'log',
        'error',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'antes'       => 'array',
            'depois'      => 'array',
            'itens'       => 'array',
            'agendado_para' => 'datetime',
            'started_at'  => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public static function statusOptions(): array
    {
        return [
            'queued'  => 'Na fila',
            'running' => 'A actualizar',
            'success' => 'Actualizado',
            'partial' => 'Actualizado com reposicoes',
            'failed'  => 'Falhou',
            'aborted' => 'Nao mexeu',
        ];
    }

    public static function statusColors(): array
    {
        return [
            'queued'  => 'gray',
            'running' => 'info',
            'success' => 'success',
            'partial' => 'warning',
            'failed'  => 'danger',
            'aborted' => 'gray',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['queued', 'running'], true);
    }

    /**
     * Um trabalho preso: o agente apanhou-o e morreu a meio. Passada uma hora
     * de "running" sem noticias, o site pode ter ficado em modo de manutencao
     * — por isso isto e para se olhar, nao para se limpar em silencio.
     */
    public function isStale(): bool
    {
        return $this->status === 'running'
            && $this->started_at !== null
            && $this->started_at->lt(now()->subMinutes((int) config('atualizacoes.stale_after_minutes', 60)));
    }


    /**
     * O proximo arranque da janela da noite.
     *
     * Antes das 02:00 e ainda esta noite; depois disso e a seguinte. Nao se
     * usa "amanha as 2" a seco porque quem carrega no botao a uma da manha
     * quer que corra dai a uma hora, nao dai a vinte e cinco.
     */
    public static function proximaJanela(): \Illuminate\Support\Carbon
    {
        $fuso = (string) config('atualizacoes.fuso', 'Europe/Lisbon');
        [$hora, $minuto] = array_pad(explode(':', (string) config('atualizacoes.janela_inicio', '02:00')), 2, '0');

        $agora = now()->setTimezone($fuso);
        $janela = $agora->copy()->setTime((int) $hora, (int) $minuto, 0);

        if ($janela->lte($agora)) {
            $janela->addDay();
        }

        return $janela->utc();
    }

    /** Ja passou a hora, mas o agente ainda nao o apanhou. */
    public function estaAEsperaDaNoite(): bool
    {
        return $this->status === 'queued'
            && $this->agendado_para !== null
            && $this->agendado_para->isFuture();
    }
}
