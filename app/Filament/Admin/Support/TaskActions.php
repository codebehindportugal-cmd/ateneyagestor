<?php

namespace App\Filament\Admin\Support;

use App\Models\ProjectTask;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;

/**
 * As acções de uma tarefa, num sítio só.
 *
 * A mesma tarefa aparece em três listas — a lista global "Tarefas", as tarefas
 * dentro de um projecto, e o "Tarefas por escolher" do painel inicial. Sem
 * isto ficavam três cópias dos mesmos botões, a divergirem com o tempo.
 */
class TaskActions
{
    /**
     * Escolher trabalho. Enquanto a tarefa não tiver dono, qualquer pessoa da
     * equipa a pode chamar a si — e fica registado quem foi.
     */
    public static function ficarCom(): Action
    {
        return Action::make('ficarCom')
            ->label('Ficar com esta')
            ->icon('heroicon-o-hand-raised')
            ->color('primary')
            ->visible(fn (ProjectTask $record) => $record->podeSerEscolhida())
            ->requiresConfirmation()
            ->modalHeading(fn (ProjectTask $record) => 'Ficar com · ' . $record->title)
            ->modalDescription('A tarefa passa a ser tua e fica a Em curso. Lê a descrição toda antes de decidir.')
            ->modalSubmitActionLabel('Fico com ela')
            ->action(function (ProjectTask $record) {
                // Duas pessoas podem carregar no botão ao mesmo tempo: quem chega
                // primeiro fica com ela, o segundo é avisado em vez de ficarem as
                // duas convencidas de que é sua.
                $ficou = ProjectTask::where('id', $record->id)
                    ->whereNull('assigned_user_id')
                    ->update([
                        'assigned_user_id' => Auth::id(),
                        'status'           => 'in_progress',
                        'updated_at'       => now(),
                    ]);

                if ($ficou === 0) {
                    Notification::make()
                        ->warning()
                        ->title('Já não estava livre')
                        ->body('Outra pessoa ficou com esta tarefa entretanto. Escolhe outra.')
                        ->send();

                    return;
                }

                $record->refresh()->logActivity('assigned', 'Escolheu esta tarefa.', [
                    'campo'  => 'Responsável',
                    'antes'  => 'por atribuir',
                    'depois' => Auth::user()?->name ?? '—',
                ]);

                Notification::make()
                    ->success()
                    ->title('A tarefa é tua')
                    ->body('Passou a Em curso. Vai comentando o que fores fazendo.')
                    ->send();
            });
    }

    public static function toggleDone(): Action
    {
        return Action::make('toggleDone')
            ->label(fn (ProjectTask $record) => $record->isDone() ? 'Reabrir' : 'Concluir')
            ->icon(fn (ProjectTask $record) => $record->isDone() ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-check')
            ->color(fn (ProjectTask $record) => $record->isDone() ? 'gray' : 'success')
            ->action(function (ProjectTask $record) {
                $record->isDone() ? $record->reopen() : $record->markDone();

                Notification::make()
                    ->success()
                    ->title($record->isDone() ? 'Tarefa concluída' : 'Tarefa reaberta')
                    ->send();
            });
    }

    public static function toggleWaiting(): Action
    {
        return Action::make('toggleWaiting')
            ->label(fn (ProjectTask $record) => $record->isWaitingOnClient() ? 'Retomar' : 'Aguardar cliente')
            ->icon(fn (ProjectTask $record) => $record->isWaitingOnClient() ? 'heroicon-o-play' : 'heroicon-o-pause')
            ->color(fn (ProjectTask $record) => $record->isWaitingOnClient() ? 'gray' : 'info')
            // Não faz sentido pôr à espera do cliente o que já terminou.
            ->visible(fn (ProjectTask $record) => ! in_array($record->status, ['done', 'cancelled'], true))
            ->action(function (ProjectTask $record) {
                $estava = $record->isWaitingOnClient();
                $estava ? $record->resumeFromClient() : $record->markWaitingOnClient();

                Notification::make()
                    ->success()
                    ->title($estava ? 'Tarefa retomada' : 'A aguardar resposta do cliente')
                    ->send();
            });
    }

    /**
     * Deixar dito o que se fez, sem ter de mudar o estado. É o que torna o
     * histórico útil quando se quer perceber porque é que algo demorou.
     */
    public static function comentar(): Action
    {
        return Action::make('comentar')
            ->label('Comentar')
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->color('gray')
            ->modalHeading(fn (ProjectTask $record) => 'Comentar · ' . $record->title)
            ->modalSubmitActionLabel('Guardar')
            ->form([
                Forms\Components\Textarea::make('body')
                    ->label('O que aconteceu')
                    ->rows(4)
                    ->required()
                    ->placeholder('Ex: fiz a query nova, falta testar com dados a sério.')
                    ->columnSpanFull(),
            ])
            ->action(function (ProjectTask $record, array $data) {
                $record->logActivity('comment', $data['body']);

                Notification::make()->success()->title('Comentário guardado')->send();
            });
    }

    public static function historico(): Action
    {
        return Action::make('historico')
            ->label('Histórico')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->modalHeading(fn (ProjectTask $record) => 'Histórico · ' . $record->title)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(fn (ProjectTask $record) => view('filament.task-history-modal', [
                'task'       => $record->loadMissing('assignedUser', 'creator'),
                'activities' => $record->activities()->with('user')->get(),
            ]));
    }

    /** Ver a tarefa toda sem ter de a editar — a descrição costuma ser longa. */
    public static function verDetalhe(): Action
    {
        return Action::make('verDetalhe')
            ->label('Abrir')
            ->icon('heroicon-o-document-text')
            ->color('gray')
            ->modalHeading(fn (ProjectTask $record) => $record->title)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(fn (ProjectTask $record) => view('filament.task-detail-modal', [
                'task' => $record->loadMissing('project', 'assignedUser'),
            ]));
    }
}
