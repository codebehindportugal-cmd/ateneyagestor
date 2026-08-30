<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\AccountingDocumentResource;
use App\Filament\Admin\Resources\InvoiceResource;
use App\Filament\Admin\Resources\ProjectResource;
use App\Models\AccountingDocument;
use App\Models\Invoice;
use App\Models\ProjectTask;
use App\Models\RoutineOccurrence;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Um mês de cada vez, com tudo o que tem data: prazos de tarefas, pré-faturas
 * a receber, documentos por pagar e as ocorrências das rotinas.
 *
 * Renderizado no servidor de propósito — o projeto não tem Vite nem passo de
 * build, e não vale a pena introduzir um só para desenhar uma grelha.
 */
class CalendarioPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Calendário';
    protected static ?string $navigationGroup = 'Operação';
    protected static ?int    $navigationSort  = 0;
    protected static string  $view            = 'filament.pages.calendario-page';

    /** Mês a ver, no formato Y-m. Fica no URL para o link ser partilhável. */
    public string $mes = '';

    /** Dia aberto no painel de baixo (Y-m-d), ou null se estiver fechado. */
    public ?string $diaAberto = null;

    /**
     * A agregação é cara (quatro consultas) e é pedida três vezes por render —
     * grelha, resumo e painel. Sem isto eram doze.
     */
    private ?array $cacheEventos = null;
    private ?string $cacheChave = null;

    public function mount(): void
    {
        $this->mes = $this->mes ?: CarbonImmutable::today()->format('Y-m');
    }

    public function getTitle(): string
    {
        return 'Calendário';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('anterior')
                ->label('Mês anterior')
                ->icon('heroicon-o-chevron-left')
                ->color('gray')
                ->action(fn () => $this->mudarMes(-1)),

            Action::make('hoje')
                ->label('Hoje')
                ->color('gray')
                ->action(fn () => $this->mes = CarbonImmutable::today()->format('Y-m')),

            Action::make('seguinte')
                ->label('Mês seguinte')
                ->icon('heroicon-o-chevron-right')
                ->color('gray')
                ->action(fn () => $this->mudarMes(1)),
        ];
    }

    private function mudarMes(int $delta): void
    {
        $this->mes = CarbonImmutable::createFromFormat('Y-m-d', $this->mes.'-01')
            ->addMonths($delta)
            ->format('Y-m');

        // Um painel de um dia que já não está à vista só confunde.
        $this->diaAberto = null;
        $this->cacheEventos = null;
    }

    public function abrirDia(string $data): void
    {
        $this->diaAberto = $this->diaAberto === $data ? null : $data;
    }

    public function fecharDia(): void
    {
        $this->diaAberto = null;
    }

    /** Tudo o que cai no dia aberto, para o painel de baixo. */
    public function eventosDoDia(): array
    {
        if (! $this->diaAberto) {
            return [];
        }

        $dia = CarbonImmutable::parse($this->diaAberto);

        return $this->eventosPorDia($dia, $dia)[$this->diaAberto] ?? [];
    }

    public function primeiroDia(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->mes.'-01')->startOfDay();
    }

    /** O mês inteiro, alargado para começar à segunda e acabar ao domingo. */
    public function semanas(): array
    {
        $inicio = $this->primeiroDia();
        $grelhaDe = $inicio->subDays($inicio->dayOfWeekIso - 1);
        $fim = $inicio->endOfMonth();
        $grelhaAte = $fim->addDays(7 - $fim->dayOfWeekIso);

        $eventos = $this->eventosPorDia($grelhaDe, $grelhaAte);

        $semanas = [];
        $dia = $grelhaDe;

        while ($dia->lessThanOrEqualTo($grelhaAte)) {
            $semana = [];

            for ($i = 0; $i < 7; $i++) {
                $chave = $dia->toDateString();
                $semana[] = [
                    'data'     => $dia,
                    'noMes'    => $dia->month === $inicio->month,
                    'hoje'     => $dia->isSameDay(CarbonImmutable::today()),
                    'eventos'  => $eventos[$chave] ?? [],
                ];
                $dia = $dia->addDay();
            }

            $semanas[] = $semana;
        }

        return $semanas;
    }

    /**
     * As quatro fontes, agrupadas por dia. Cada evento traz sempre: tipo (para a
     * cor), título, link e um estado já resolvido — a Blade não faz consultas.
     */
    private function eventosPorDia(CarbonImmutable $de, CarbonImmutable $ate): array
    {
        $chave = $de->toDateString().'|'.$ate->toDateString();

        if ($this->cacheChave === $chave && $this->cacheEventos !== null) {
            return $this->cacheEventos;
        }

        $porDia = [];

        $juntar = function (?string $data, array $evento) use (&$porDia) {
            if ($data) {
                $porDia[$data][] = $evento;
            }
        };

        // 1. Rotinas — o que se repete todas as semanas/meses
        RoutineOccurrence::with('routine')
            ->whereBetween('due_date', [$de->toDateString(), $ate->toDateString()])
            ->get()
            ->each(fn (RoutineOccurrence $o) => $juntar(
                $o->due_date?->toDateString(),
                [
                    'tipo'      => $o->routine?->isPagamento() ? 'rotina_pagamento' : 'rotina_tarefa',
                    'titulo'    => $o->routine?->nome ?? 'Rotina',
                    'valor'     => $o->amount_cents,
                    'concluido' => $o->status !== 'pendente',
                    'atrasado'  => $o->estaAtrasada(),
                    'url'       => null,
                    'id'        => $o->id,
                ],
            ));

        // 2. Prazos das tarefas dos projetos
        ProjectTask::with('project')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$de->toDateString(), $ate->toDateString()])
            ->get()
            ->each(fn (ProjectTask $t) => $juntar(
                $t->due_date?->toDateString(),
                [
                    'tipo'      => 'tarefa',
                    'titulo'    => $t->title,
                    'contexto'  => $t->project?->name,
                    'valor'     => null,
                    'concluido' => in_array($t->status, ['done', 'cancelled'], true),
                    'atrasado'  => $t->isOverdue(),
                    'url'       => $t->project_id
                        ? ProjectResource::getUrl('tasks', ['record' => $t->project_id])
                        : null,
                    'id'        => $t->id,
                ],
            ));

        // 3. Pré-faturas a receber
        Invoice::with('client')
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$de->toDateString(), $ate->toDateString()])
            ->get()
            ->each(fn (Invoice $i) => $juntar(
                $i->due_at?->toDateString(),
                [
                    'tipo'      => 'receber',
                    'titulo'    => $i->number ?: 'Pré-fatura',
                    'contexto'  => $i->client?->name,
                    'valor'     => $i->amount_cents,
                    'concluido' => $i->paid_at !== null,
                    'atrasado'  => $i->paid_at === null && $i->due_at?->isPast(),
                    'url'       => InvoiceResource::getUrl('edit', ['record' => $i->id]),
                    'id'        => $i->id,
                ],
            ));

        // 4. Documentos de fornecedor por pagar
        AccountingDocument::whereNotNull('date')
            ->whereBetween('date', [$de->toDateString(), $ate->toDateString()])
            ->get()
            ->each(fn (AccountingDocument $d) => $juntar(
                $d->date?->toDateString(),
                [
                    'tipo'      => 'pagar',
                    'titulo'    => $d->fornecedor ?: ($d->invoice_number ?: 'Documento'),
                    'contexto'  => $d->invoice_number,
                    'valor'     => $d->amount_cents,
                    'concluido' => $d->estado === 'pago',
                    'atrasado'  => $d->estado !== 'pago' && $d->date?->isPast(),
                    'url'       => AccountingDocumentResource::getUrl('edit', ['record' => $d->id]),
                    'id'        => $d->id,
                ],
            ));

        $this->cacheChave = $chave;
        $this->cacheEventos = $porDia;

        return $porDia;
    }

    /** Marca uma ocorrência de rotina como feita, sem sair do calendário. */
    public function marcarRotina(int $id): void
    {
        $ocorrencia = RoutineOccurrence::find($id);

        if (! $ocorrencia) {
            return;
        }

        $ocorrencia->estaPendente() ? $ocorrencia->marcarFeito() : $ocorrencia->reabrir();

        // Sem isto o render seguinte servia o estado antigo do cache.
        $this->cacheEventos = null;
        $this->cacheChave = null;

        Notification::make()
            ->success()
            ->title($ocorrencia->estaPendente() ? 'Reaberta' : 'Marcada como feita')
            ->send();
    }

    /** Totais do mês, para o cabeçalho responder a "quanto sai e quanto entra". */
    public function resumo(): array
    {
        $inicio = $this->primeiroDia();
        $fim = $inicio->endOfMonth();

        // A mesma janela alargada que a grelha usa, para aproveitar o cache.
        $de = $inicio->subDays($inicio->dayOfWeekIso - 1);
        $ate = $fim->addDays(7 - $fim->dayOfWeekIso);

        $eventos = collect($this->eventosPorDia($de, $ate))
            ->filter(fn ($_, $data) => str_starts_with($data, $this->mes))
            ->flatten(1);

        $soma = fn (string $tipo) => $eventos
            ->where('tipo', $tipo)
            ->sum(fn ($e) => (int) ($e['valor'] ?? 0));

        return [
            'a_receber'  => $soma('receber'),
            'a_pagar'    => $soma('pagar') + $soma('rotina_pagamento'),
            'por_fazer'  => $eventos->where('concluido', false)->count(),
            'atrasados'  => $eventos->where('atrasado', true)->count(),
        ];
    }
}
