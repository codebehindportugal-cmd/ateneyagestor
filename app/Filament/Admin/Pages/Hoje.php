<?php

namespace App\Filament\Admin\Pages;

use App\Enums\BackupStatus;
use App\Enums\MonitorStatus;
use App\Filament\Admin\Resources\BackupRunResource;
use App\Filament\Admin\Resources\HardeningAuditResource;
use App\Filament\Admin\Resources\ServerResource;
use App\Filament\Admin\Resources\SiteMonitorResource;
use App\Filament\Admin\Resources\SyncProjectResource;
use App\Models\BackupRun;
use App\Models\HardeningAudit;
use App\Models\ProjectTask;
use App\Models\Server;
use App\Models\SiteMonitor;
use App\Models\SyncProject;
use Filament\Pages\Dashboard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * "Hoje" — o painel inicial (01/10/2026), no lugar do Dashboard do Filament.
 *
 * Em cima: quatro números e a lista "Precisa de ti", que junta num só sítio o
 * que hoje está espalhado por cinco páginas (sites em baixo, sincronizadores
 * com erro, backups falhados, segurança por corrigir, auditorias que não
 * chegaram ao fim), ordenada pelo que é mais grave. Por baixo ficam os widgets
 * que já existiam, sem mudar.
 *
 * Tudo o que é infraestrutura só aparece ao administrador, como nos widgets.
 * Cada bloco lê a sua tabela dentro de um try: uma tabela que ainda não migrou
 * não pode deitar abaixo a página inicial.
 */
class Hoje extends Dashboard
{
    protected static ?string $title = 'Hoje';

    protected static ?string $navigationLabel = 'Hoje';

    protected static ?string $navigationIcon = 'heroicon-o-sun';

    protected static string $view = 'filament.admin.pages.hoje';

    public function getHeading(): string
    {
        return '';
    }

    protected function getViewData(): array
    {
        $admin = Auth::user()?->isAdmin() === true;
        $agora = Carbon::now();

        $auditorias = $admin ? $this->ultimasAuditorias() : collect();

        return [
            'admin'      => $admin,
            'dataHoje'   => $this->dataPorExtenso($agora),
            'saudacao'   => $this->saudacao($agora) . ', ' . (str(Auth::user()?->name ?? '')->before(' ')->toString() ?: 'olá'),
            'kpis'       => $this->kpis($admin, $auditorias),
            'atencao'    => $admin ? $this->atencao($auditorias) : [],
            'servidores' => $admin ? $this->servidores($auditorias) : [],
            'urlAuditorias' => HardeningAuditResource::getUrl('index'),
        ];
    }

    /** A auditoria de segurança mais recente (já acabada) de cada servidor. */
    private function ultimasAuditorias(): Collection
    {
        return $this->seguro(fn () => HardeningAudit::query()
            ->whereIn('id', fn ($q) => $q->selectRaw('max(id)')
                ->from((new HardeningAudit)->getTable())
                ->where('estado', '!=', 'pendente')
                ->groupBy('server_id'))
            ->with('server')
            ->get()
            ->filter(fn (HardeningAudit $a) => $a->server?->is_active)
            ->keyBy('server_id'), collect());
    }

    private function kpis(bool $admin, Collection $auditorias): array
    {
        $tarefas = $this->seguro(fn () => ProjectTask::query()
            ->where('assigned_user_id', Auth::id())
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count(), 0);

        $minhas = [
            'rotulo' => 'As minhas tarefas',
            'num'    => (string) $tarefas,
            'desc'   => $tarefas === 1 ? 'por fechar' : 'por fechar',
            'tom'    => '',
            'url'    => \App\Filament\Admin\Resources\ProjectTaskResource::getUrl('index'),
        ];

        if (! $admin) {
            return [$minhas];
        }

        $falhas = (int) $auditorias->sum('falhas');
        $comFalhas = $auditorias->where('falhas', '>', 0)->count();

        $emBaixo = $this->seguro(fn () => SiteMonitor::where('is_active', true)
            ->where('status', MonitorStatus::Down)->pluck('name'), collect());

        [$ok, $total] = $this->seguro(function () {
            $runs = BackupRun::where('started_at', '>=', now()->subDay())->get(['status']);

            return [$runs->where('status', BackupStatus::Success)->count(), $runs->count()];
        }, [0, 0]);

        return [
            [
                'rotulo' => 'Segurança por corrigir',
                'num'    => (string) $falhas,
                'desc'   => $falhas > 0 ? "em {$comFalhas} " . ($comFalhas === 1 ? 'servidor' : 'servidores') : 'nada por corrigir',
                'tom'    => $falhas > 0 ? 'hj-mau' : 'hj-bom',
                'url'    => HardeningAuditResource::getUrl('index'),
            ],
            [
                'rotulo' => 'Sites em baixo',
                'num'    => (string) $emBaixo->count(),
                'desc'   => $emBaixo->isEmpty() ? 'todos a responder' : $emBaixo->take(3)->join(', '),
                'tom'    => $emBaixo->isEmpty() ? 'hj-bom' : 'hj-mau',
                'url'    => SiteMonitorResource::getUrl('index'),
            ],
            [
                'rotulo' => 'Backups nas últimas 24 h',
                'num'    => $total > 0 ? "{$ok}/{$total}" : '—',
                'desc'   => $total === 0 ? 'nenhum registado' : ($ok === $total ? 'todos bem' : ($total - $ok) . ' com falha'),
                'tom'    => $total > 0 && $ok < $total ? 'hj-mau' : '',
                'url'    => BackupRunResource::getUrl('index'),
            ],
            $minhas,
        ];
    }

    /** "Precisa de ti": o mais grave primeiro, no máximo 8 linhas. */
    private function atencao(Collection $auditorias): array
    {
        $linhas = [];

        foreach ($this->seguro(fn () => SiteMonitor::where('is_active', true)->where('status', MonitorStatus::Down)->get(), []) as $m) {
            $linhas[] = $this->linha(0, 'falha', 'Site em baixo', $m->name,
                $m->last_error ?: 'Não responde desde ' . optional($m->went_down_at)->format('d/m H:i') . '.',
                'Ver', SiteMonitorResource::getUrl('index'));
        }

        foreach ($this->seguro(fn () => Server::where('is_active', true)->where('ping_status', 'down')->get(), []) as $s) {
            $linhas[] = $this->linha(0, 'falha', 'Servidor sem resposta', $s->name,
                $s->ping_error ?: 'O ping deixou de responder.', 'Ver', ServerResource::getUrl('index'));
        }

        $falhados = $this->seguro(fn () => BackupRun::with('server')
            ->where('started_at', '>=', now()->subDay())
            ->where('status', BackupStatus::Failed)
            ->get()
            ->groupBy('server_id'), collect());

        foreach ($falhados as $runs) {
            $linhas[] = $this->linha(1, 'falha', 'Backup falhou', $runs->first()->server?->name ?? '—',
                str($runs->first()->error ?? '')->limit(140)->toString() ?: $runs->count() . ' cópia(s) falhada(s) nas últimas 24 h.',
                'Ver', BackupRunResource::getUrl('index'));
        }

        foreach ($this->seguro(fn () => SyncProject::where('is_active', true)->where('status', 'error')->get(), []) as $p) {
            $linhas[] = $this->linha(1, 'falha', 'Sincronizador com erro', $p->name,
                'Última corrida ' . optional($p->last_run_at)->diffForHumans() . '.', 'Ver', SyncProjectResource::getUrl('index'));
        }

        foreach ($auditorias as $a) {
            $url = HardeningAuditResource::getUrl('view', ['record' => $a]);
            $nome = $a->server?->name ?? '—';

            if ($a->falhas > 0) {
                $que = collect($a->resultados ?? [])->where('estado', 'falha')->pluck('label')->join(', ');
                $linhas[] = $this->linha(2, 'falha', $a->falhas . ' por corrigir', $nome, $que, 'Rever', $url);
            } elseif (in_array($a->estado, ['incompleta', 'erro'], true)) {
                $linhas[] = $this->linha(3, 'aviso', $a->estado === 'erro' ? 'Auditoria falhou' : 'Auditoria incompleta', $nome,
                    $a->estado === 'erro' ? (string) str($a->erro ?? '')->limit(140) : 'A resposta veio cortada — há verificações por ler.',
                    'Ver', $url);
            }
        }

        usort($linhas, fn ($x, $y) => $x['peso'] <=> $y['peso']);

        return array_slice($linhas, 0, 8);
    }

    private function servidores(Collection $auditorias): array
    {
        $servidores = $this->seguro(fn () => Server::where('is_active', true)->orderBy('name')->get(), collect());

        return $servidores->map(function (Server $s) use ($auditorias) {
            $a = $auditorias->get($s->id);

            [$estado, $tom, $peso] = match (true) {
                $s->ping_status === 'down' => ['Sem resposta', 'falha', 0],
                $a === null                => ['Sem auditoria', 'cinza', 3],
                $a->falhas > 0             => [$a->falhas . ' por corrigir', 'falha', 1],
                $a->estado === 'incompleta' || $a->estado === 'erro' => [HardeningAudit::estadoLabels()[$a->estado], 'aviso', 2],
                $a->avisos > 0             => [$a->avisos . ' ' . ($a->avisos === 1 ? 'aviso' : 'avisos'), 'aviso', 2],
                default                    => ['Tudo bem', 'ok', 4],
            };

            return [
                'nome'   => $s->name,
                'host'   => $s->host,
                'painel' => $s->hasPlesk() ? 'Plesk' : 'Ubuntu',
                'estado' => $estado,
                'tom'    => $tom,
                'peso'   => $peso,
                'url'    => $a ? HardeningAuditResource::getUrl('view', ['record' => $a]) : null,
            ];
        })->sortBy([['peso', 'asc'], ['nome', 'asc']])->values()->all();
    }

    private function linha(int $peso, string $tom, string $titulo, string $onde, string $texto, string $acao, string $url): array
    {
        return compact('peso', 'tom', 'titulo', 'onde', 'texto', 'acao', 'url');
    }

    private function dataPorExtenso(Carbon $d): string
    {
        $dias = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
        $meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

        return $dias[$d->dayOfWeek] . ', ' . $d->day . ' de ' . $meses[$d->month - 1];
    }

    private function saudacao(Carbon $d): string
    {
        return match (true) {
            $d->hour < 6  => 'Boa noite',
            $d->hour < 13 => 'Bom dia',
            $d->hour < 20 => 'Boa tarde',
            default       => 'Boa noite',
        };
    }

    /** Uma tabela por migrar, ou um erro num bloco, não pode partir a página. */
    private function seguro(callable $f, mixed $porOmissao): mixed
    {
        try {
            return $f();
        } catch (\Throwable $e) {
            report($e);

            return $porOmissao;
        }
    }
}
