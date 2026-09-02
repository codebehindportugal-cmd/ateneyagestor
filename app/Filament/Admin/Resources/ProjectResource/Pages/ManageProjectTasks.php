<?php

namespace App\Filament\Admin\Resources\ProjectResource\Pages;

use App\Filament\Admin\Resources\ProjectResource;
use App\Models\ClaudeRun;
use App\Models\ProjectTask;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ManageProjectTasks extends ManageRelatedRecords
{
    protected static string $resource = ProjectResource::class;

    protected static string $relationship = 'tasks';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $title = 'Tarefas';

    public static function getNavigationLabel(): string
    {
        return 'Tarefas';
    }

    /** Quem está a ver a página manda em quase tudo o que se segue. */
    protected function isAdmin(): bool
    {
        return Auth::user()?->isAdmin() === true;
    }

    /** As pessoas a quem se pode entregar uma tarefa. */
    protected function assignableUsers(): array
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function getSubheading(): ?string
    {
        $project = $this->getOwnerRecord();

        // Um estagiário vê o resumo do trabalho dele, não o da equipa toda.
        $base = fn () => $this->isAdmin()
            ? $project->tasks()
            : $project->tasks()->where('assigned_user_id', Auth::id());

        $total = $base()->count();

        if ($total === 0) {
            return $this->isAdmin()
                ? 'Ainda não há tarefas registadas neste projecto.'
                : 'Ainda não tens tarefas atribuídas neste projecto.';
        }

        $done  = $base()->where('status', 'done')->count();
        $open  = $total - $done;
        $pct   = (int) round($done / $total * 100);
        $hours = (float) $base()->sum('hours');

        $line = "{$done} de {$total} concluídas ({$pct}%) · {$open} por fazer";

        if ($hours > 0) {
            $line .= ' · ' . number_format($hours, 2, ',', '.') . ' h registadas';
        }

        if ($this->isAdmin()) {
            $porAtribuir = $project->tasks()
                ->whereNull('assigned_user_id')
                ->whereNotIn('status', ['done', 'cancelled'])
                ->count();

            if ($porAtribuir > 0) {
                $line .= " · {$porAtribuir} por atribuir";
            }
        }

        return $line;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('Tarefa')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            // Só quem manda distribui trabalho. O estagiário vê quem é o
            // responsável, mas não o troca.
            Forms\Components\Select::make('assigned_user_id')
                ->label('Responsável')
                ->options(fn () => $this->assignableUsers())
                ->searchable()
                ->preload()
                ->placeholder('Por atribuir')
                ->disabled(fn () => ! $this->isAdmin())
                ->dehydrated(fn () => $this->isAdmin())
                ->helperText(fn () => $this->isAdmin()
                    ? 'Quem fica encarregue. Fica registado no histórico da tarefa.'
                    : 'Só o administrador distribui as tarefas.'),

            Forms\Components\Select::make('status')
                ->label('Estado')
                ->options(ProjectTask::statusOptions())
                ->default('pending')
                ->required(),

            Forms\Components\DatePicker::make('due_date')
                ->label('Prazo')
                ->displayFormat('d/m/Y')
                ->native(false),

            Forms\Components\TextInput::make('hours')
                ->label('Horas')
                ->numeric()
                ->step(0.25)
                ->minValue(0)
                ->helperText('Horas de trabalho a facturar nesta tarefa.'),

            Forms\Components\Textarea::make('description')
                ->label('Notas')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        $admin = $this->isAdmin();

        return $table
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['lastClaudeRun', 'assignedUser'])
                // A rede de segurança do lado da consulta: um estagiário nunca
                // recebe do servidor tarefas que não sejam dele.
                ->when(! $admin, fn (Builder $q) => $q->where('assigned_user_id', Auth::id())))
            ->reorderable($admin ? 'position' : null)
            ->defaultSort('position')
            ->columns([
                Tables\Columns\IconColumn::make('status')
                    ->label('')
                    ->icon(fn (ProjectTask $record) => $record->isDone()
                        ? 'heroicon-s-check-circle'
                        : ($record->status === 'cancelled' ? 'heroicon-o-x-circle' : 'heroicon-o-clock'))
                    ->color(fn (ProjectTask $record) => ProjectTask::statusColor($record->status)),

                Tables\Columns\TextColumn::make('title')
                    ->label('Tarefa')
                    ->searchable()
                    ->wrap()
                    ->weight('medium')
                    ->color(fn (ProjectTask $record) => $record->isDone() ? 'gray' : null)
                    ->description(fn (ProjectTask $record) => $record->description),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Responsável')
                    ->badge()
                    ->color(fn ($state) => $state ? 'primary' : 'gray')
                    ->placeholder('Por atribuir')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ProjectTask::statusOptions()[$state] ?? $state)
                    ->color(fn ($state) => ProjectTask::statusColor($state)),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Prazo')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable()
                    ->color(fn (ProjectTask $record) => $record->isOverdue() ? 'danger' : null),

                Tables\Columns\TextColumn::make('hours')
                    ->label('Horas')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => $state === null ? null : number_format((float) $state, 2, ',', '.') . ' h')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Concluída em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->description(fn (ProjectTask $record) => $record->completedBy?->name),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('assigned_user_id')
                    ->label('Responsável')
                    ->options(fn () => $this->assignableUsers())
                    ->multiple()
                    ->visible($admin),

                Tables\Filters\Filter::make('por_atribuir')
                    ->label('Por atribuir')
                    ->query(fn (Builder $query) => $query->whereNull('assigned_user_id'))
                    ->visible($admin),

                Tables\Filters\Filter::make('minhas')
                    ->label('As minhas')
                    ->query(fn (Builder $query) => $query->where('assigned_user_id', Auth::id()))
                    ->visible($admin),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(ProjectTask::statusOptions())
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('concluidas')
                    ->label('Concluídas')
                    ->placeholder('Todas')
                    ->trueLabel('Só as feitas')
                    ->falseLabel('Só as que faltam')
                    ->queries(
                        true: fn (Builder $query) => $query->where('status', 'done'),
                        false: fn (Builder $query) => $query->where('status', '!=', 'done'),
                        blank: fn (Builder $query) => $query,
                    ),

                Tables\Filters\Filter::make('atrasadas')
                    ->label('Em atraso')
                    ->query(fn (Builder $query) => $query
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '<', now())
                        ->whereNotIn('status', ProjectTask::NOT_OVERDUE_STATUSES)),

                Tables\Filters\Filter::make('a_aguardar_cliente')
                    ->label('A aguardar cliente')
                    ->query(fn (Builder $query) => $query->where('status', 'waiting_client')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nova tarefa'),
            ])
            ->actions([
                // Manda a tarefa ao Claude. O painel so poe o pedido na fila; quem
                // corre e o `claude:work`, na maquina onde o repositorio vive.
                // Só o administrador: isto corre codigo no PC dele.
                Tables\Actions\Action::make('pedirClaude')
                    ->label('Resolver com o Claude')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->visible(fn (ProjectTask $record) => $admin
                        && ! in_array($record->status, ['done', 'cancelled'], true)
                        && ! ($record->lastClaudeRun?->isPending() && ! $record->lastClaudeRun->isStale()))
                    ->requiresConfirmation()
                    ->modalHeading('Mandar esta tarefa ao Claude')
                    ->modalDescription(fn () => $this->getOwnerRecord()->hasCode()
                        ? 'Ele lê o código do projecto (' . $this->getOwnerRecord()->codeSourceLabel() . ') e escreve aqui o diagnóstico e o plano. Não altera ficheiros, não faz commits e não toca em servidores.'
                        : 'Este projecto não tem código configurado, por isso ele planeia com o contexto do painel. Para lhe dar o código, preenche a secção Código na ficha do projecto.')
                    ->modalSubmitActionLabel('Mandar')
                    ->action(function (ProjectTask $record) {
                        ClaudeRun::create([
                            'project_task_id' => $record->id,
                            'status'          => 'queued',
                            'mode'            => 'diagnose',
                            'requested_by'    => Auth::id(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Enviado ao Claude')
                            ->body('Fica na fila ate o worker o apanhar. A resposta aparece nesta linha.')
                            ->send();
                    }),

                // Continuar a conversa. A sessao anterior ja tem o contexto todo, por
                // isso so vai a instrucao nova — e daqui, ao contrario do botao de
                // cima, ele pode mexer nos ficheiros.
                Tables\Actions\Action::make('continuarClaude')
                    ->label('Continuar')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('warning')
                    ->visible(fn (ProjectTask $record) => $admin && $record->lastClaudeRun?->isDone() === true)
                    ->modalHeading(fn (ProjectTask $record) => 'Continuar com o Claude · ' . $record->title)
                    ->modalDescription('Ele retoma a conversa anterior, com tudo o que já leu e concluiu.')
                    ->modalSubmitActionLabel('Enviar')
                    ->form([
                        Forms\Components\Textarea::make('instrucao')
                            ->label('O que queres que ele faça agora')
                            ->rows(5)
                            ->required()
                            ->placeholder('Ex: avança com o ponto 1 do plano, mas deixa a facturação como está.')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('pode_alterar')
                            ->label('Pode alterar ficheiros')
                            ->default(true)
                            ->helperText('Altera e pára: não faz commit, não cria ramos, não corre migrations nem toca no .env. Vês o resultado com `git diff`.'),
                    ])
                    ->action(function (ProjectTask $record, array $data) {
                        ClaudeRun::create([
                            'project_task_id' => $record->id,
                            'status'          => 'queued',
                            'mode'            => $data['pode_alterar'] ? 'apply' : 'continue',
                            'follow_up'       => $data['instrucao'],
                            'requested_by'    => Auth::id(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Enviado ao Claude')
                            ->body($data['pode_alterar']
                                ? 'Vai retomar a conversa e alterar os ficheiros. Depois confirma com git diff.'
                                : 'Vai retomar a conversa e responder, sem tocar em ficheiros.')
                            ->send();
                    }),

                Tables\Actions\Action::make('verClaude')
                    ->label(fn (ProjectTask $record) => match ($record->lastClaudeRun?->status) {
                        'done'   => 'Ver resposta',
                        'failed' => 'Ver erro',
                        default  => 'Claude a trabalhar',
                    })
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color(fn (ProjectTask $record) => ClaudeRun::statusColor($record->lastClaudeRun?->status))
                    ->visible(fn (ProjectTask $record) => $admin && $record->lastClaudeRun !== null)
                    ->modalHeading(fn (ProjectTask $record) => 'Claude · ' . $record->title)
                    ->modalContent(fn (ProjectTask $record) => view('filament.claude-run-modal', [
                        'run' => $record->lastClaudeRun->loadMissing('requestedBy', 'task.project'),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),

                Tables\Actions\Action::make('toggleDone')
                    ->label(fn (ProjectTask $record) => $record->isDone() ? 'Reabrir' : 'Concluir')
                    ->icon(fn (ProjectTask $record) => $record->isDone() ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-check')
                    ->color(fn (ProjectTask $record) => $record->isDone() ? 'gray' : 'success')
                    ->action(function (ProjectTask $record) {
                        $record->isDone() ? $record->reopen() : $record->markDone();

                        Notification::make()
                            ->success()
                            ->title($record->isDone() ? 'Tarefa concluída' : 'Tarefa reaberta')
                            ->send();
                    }),

                Tables\Actions\Action::make('toggleWaiting')
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
                    }),

                // Deixar dito o que se fez, sem ter de mudar o estado. É o que
                // torna o histórico útil quando se quer perceber porque é que
                // uma tarefa demorou.
                Tables\Actions\Action::make('comentar')
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
                    }),

                Tables\Actions\Action::make('historico')
                    ->label('Histórico')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading(fn (ProjectTask $record) => 'Histórico · ' . $record->title)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->modalContent(fn (ProjectTask $record) => view('filament.task-history-modal', [
                        'task'       => $record->loadMissing('assignedUser', 'creator'),
                        'activities' => $record->activities()->with('user')->get(),
                    ])),

                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make()->label('Apagar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('atribuir')
                        ->label('Atribuir a…')
                        ->icon('heroicon-o-user-plus')
                        ->color('primary')
                        ->visible($admin)
                        ->form([
                            Forms\Components\Select::make('assigned_user_id')
                                ->label('Responsável')
                                ->options(fn () => $this->assignableUsers())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each(fn (ProjectTask $task) => $task
                                ->forceFill(['assigned_user_id' => $data['assigned_user_id']])
                                ->save());

                            Notification::make()
                                ->success()
                                ->title('Tarefas atribuídas')
                                ->body($records->count() . ' tarefas passaram para ' . (User::find($data['assigned_user_id'])?->name ?? 'a pessoa escolhida') . '.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('concluir')
                        ->label('Marcar como feitas')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->markDone())
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading($admin ? 'Sem tarefas' : 'Nada para ti aqui')
            ->emptyStateDescription($admin
                ? 'Adiciona as tarefas do projecto para saberes o que já está feito e o que falta.'
                : 'Ainda não te foi atribuída nenhuma tarefa neste projecto.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }
}
