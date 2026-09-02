<?php

namespace App\Filament\Admin\Resources\ProjectTaskResource\Pages;

use App\Filament\Admin\Resources\ProjectTaskResource;
use App\Models\ProjectTask;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListProjectTasks extends ListRecords
{
    protected static string $resource = ProjectTaskResource::class;

    public function getTitle(): string
    {
        return 'Tarefas';
    }

    public function getSubheading(): ?string
    {
        return Auth::user()?->isAdmin()
            ? 'Todas as tarefas de todos os projectos. As que não têm responsável estão em "Por escolher".'
            : 'As tuas tarefas e as que estão livres. Para começar uma livre, carrega em "Ficar com esta".';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nova tarefa'),
        ];
    }

    public function getTabs(): array
    {
        $minhas = ProjectTaskResource::getEloquentQuery()
            ->where('assigned_user_id', Auth::id())
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();

        $livres = ProjectTaskResource::getEloquentQuery()->porEscolher()->count();

        return [
            'minhas' => Tab::make('As minhas')
                ->icon('heroicon-o-user')
                ->badge($minhas ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('assigned_user_id', Auth::id())
                    ->whereNotIn('status', ['done', 'cancelled'])),

            'escolher' => Tab::make('Por escolher')
                ->icon('heroicon-o-hand-raised')
                ->badge($livres ?: null)
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $query) => $query->porEscolher()),

            'abertas' => Tab::make('Todas as abertas')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereNotIn('status', ['done', 'cancelled'])),

            'feitas' => Tab::make('Feitas')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'done')),

            'todas' => Tab::make('Todas')
                ->icon('heroicon-o-list-bullet'),
        ];
    }

    /**
     * Quem não tem nada em mãos cai no balcão das tarefas livres, e não numa
     * lista vazia a dizer "não tens nada" — que é o que faz uma pessoa nova
     * pensar que o painel está partido.
     */
    public function getDefaultActiveTab(): string | int | null
    {
        $minhas = ProjectTaskResource::getEloquentQuery()
            ->where('assigned_user_id', Auth::id())
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();

        return $minhas > 0 ? 'minhas' : 'escolher';
    }
}
