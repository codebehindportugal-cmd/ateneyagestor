<?php

namespace App\Filament\Client\Resources\TicketResource\RelationManagers;

use App\Models\Attachment;
use App\Models\TicketMessage;
use App\Services\AttachmentService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Conversa';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('body')
                ->label('A tua resposta')
                ->required()
                ->rows(4),

            // O sitio onde o cliente ja escreve e' o sitio onde ele tem a foto
            // na mao. Mandar por email para depois alguem a arrastar para ca
            // era o passo onde as fotos se perdiam.
            Forms\Components\FileUpload::make('ficheiros')
                ->label('Juntar fotos ou ficheiros')
                ->disk('local')
                ->directory('tmp-anexos')
                ->multiple()
                ->maxSize(51200)
                ->storeFileNamesIn('nomes_originais')
                ->helperText('Fotos do erro, documentos, o que ajudar. Até 50 MB cada.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->modifyQueryUsing(fn ($query) => $query->with('anexos'))
            ->columns([
                Tables\Columns\TextColumn::make('authorName')
                    ->label('Quem')
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

                        // Nao sao colunas da tabela: se fossem no create(),
                        // rebentavam por coluna inexistente.
                        unset($data['ficheiros'], $data['nomes_originais']);

                        $data['author_type'] = 'client';
                        $data['author_client_id'] = Filament::auth()->id();

                        $mensagem = $this->getRelationship()->create($data);

                        $this->guardarAnexos($mensagem, $caminhos, $nomes);

                        return $mensagem;
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'asc');
    }

    /**
     * A mensagem fica escrita mesmo que um ficheiro falhe. O contrario — perder
     * o texto porque o NAS esteve em baixo — obrigava o cliente a escrever tudo
     * outra vez, e e' quando ele desiste e liga para o telemovel.
     */
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
                    origem: 'cliente',
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
                ->body('Não consegui guardar: '.implode(', ', $falhados).'. Tenta juntá-los noutra resposta.')
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
                '<a href="%s" class="text-primary-600 underline">%s</a> <span class="text-gray-400 text-xs">%s</span>',
                route('cliente.anexos.download', $anexo),
                e($anexo->name),
                e($anexo->formatted_size),
            ))
            ->implode('<br>');

        return new HtmlString($linhas);
    }
}
