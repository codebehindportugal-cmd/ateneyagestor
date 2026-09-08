<?php

namespace App\Filament\Admin\Resources\TicketResource\RelationManagers;

use App\Models\Attachment;
use App\Models\TicketMessage;
use App\Services\AttachmentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Mensagens';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('body')
                ->label('Resposta')
                ->required()
                ->rows(4),

            Forms\Components\FileUpload::make('ficheiros')
                ->label('Juntar ficheiros')
                ->disk('local')
                ->directory('tmp-anexos')
                ->multiple()
                ->maxSize(51200)
                ->storeFileNamesIn('nomes_originais')
                ->helperText('Capturas de ecrã, documentos, o que o cliente precise de ver. Até 50 MB cada.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->modifyQueryUsing(fn ($query) => $query->with('anexos'))
            ->columns([
                Tables\Columns\TextColumn::make('authorName')
                    ->label('Autor')
                    ->state(fn ($record) => $record->authorName()),

                Tables\Columns\TextColumn::make('body')
                    ->label('Mensagem')
                    ->wrap(),

                Tables\Columns\TextColumn::make('anexos')
                    ->label('Ficheiros')
                    ->state(fn (TicketMessage $record) => static::listaDeAnexos($record))
                    ->html()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Quando')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Responder')
                    ->using(function (array $data) {
                        $caminhos = array_values((array) ($data['ficheiros'] ?? []));
                        $nomes = array_values((array) ($data['nomes_originais'] ?? []));

                        unset($data['ficheiros'], $data['nomes_originais']);

                        $data['author_type'] = 'staff';
                        $data['author_user_id'] = Auth::id();

                        $mensagem = $this->getRelationship()->create($data);

                        $this->guardarAnexos($mensagem, $caminhos, $nomes);

                        return $mensagem;
                    }),
            ])
            ->defaultSort('created_at', 'asc');
    }

    /** A resposta fica escrita mesmo que um ficheiro falhe a subir. */
    private function guardarAnexos(TicketMessage $mensagem, array $caminhos, array $nomes): void
    {
        if ($caminhos === []) {
            return;
        }

        $servico = app(AttachmentService::class);
        $falhados = [];

        foreach ($caminhos as $indice => $caminho) {
            $original = $nomes[$indice] ?? basename($caminho);

            try {
                $servico->processUpload(
                    attachable: $mensagem,
                    tempDiskPath: $caminho,
                    originalName: $original,
                    name: pathinfo($original, PATHINFO_FILENAME),
                    origem: 'equipa',
                    uploadedBy: Auth::id(),
                );
            } catch (\Throwable $e) {
                report($e);
                $falhados[] = $original;
            }
        }

        if ($falhados !== []) {
            Notification::make()
                ->warning()
                ->title('A resposta foi enviada, mas houve ficheiros que não subiram')
                ->body('Não consegui guardar: '.implode(', ', $falhados).'.')
                ->persistent()
                ->send();
        }
    }

    public static function listaDeAnexos(TicketMessage $mensagem): ?HtmlString
    {
        if ($mensagem->anexos->isEmpty()) {
            return null;
        }

        $linhas = $mensagem->anexos
            ->map(fn (Attachment $anexo) => sprintf(
                '<a href="%s" class="text-primary-600 underline">%s</a>'
                .' <span class="text-gray-400 text-xs">%s · %s</span>',
                route('anexos.download', $anexo),
                e($anexo->name),
                e($anexo->formatted_size),
                e($anexo->origem_label),
            ))
            ->implode('<br>');

        return new HtmlString($linhas);
    }
}
