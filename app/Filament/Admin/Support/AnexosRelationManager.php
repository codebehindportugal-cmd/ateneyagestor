<?php

namespace App\Filament\Admin\Support;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * A pasta de ficheiros de um projecto ou de uma tarefa.
 *
 * Uma classe so' para os dois: a relacao chama-se `anexos` nos dois modelos, e
 * o Filament so' precisa disso. Duas copias divergiam ao segundo campo novo.
 */
class AnexosRelationManager extends RelationManager
{
    protected static string $relationship = 'anexos';

    protected static ?string $title = 'Ficheiros';

    protected static ?string $icon = 'heroicon-o-paper-clip';

    public static function getBadge(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): ?string
    {
        $total = $ownerRecord->anexos()->count();

        return $total > 0 ? (string) $total : null;
    }

    public function form(Forms\Form $form): Forms\Form
    {
        // Só serve para editar o que já lá está — carregar é pela acção
        // "Carregar ficheiros", que precisa de passar pelo serviço.
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('origem')
                ->label('Origem')
                ->options(Attachment::origens())
                ->required(),

            Forms\Components\Textarea::make('notes')
                ->label('Notas')
                ->rows(3)
                ->placeholder('Ex: foto do quadro eléctrico que o cliente mandou por WhatsApp.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->emptyStateHeading('Ainda não há ficheiros')
            ->emptyStateDescription('Fotos, PDFs, orçamentos — o que o cliente mandar e o que a equipa produzir.')
            ->columns([
                // Um icone e nao a miniatura: o ficheiro pode estar no NAS, e
                // cada miniatura era um SSH para o ir buscar. Vinte fotos numa
                // lista faziam vinte ligacoes so' para desenhar a pagina.
                Tables\Columns\IconColumn::make('mime_type')
                    ->label('')
                    ->icon(fn (Attachment $record) => match (true) {
                        $record->isImage() => 'heroicon-o-photo',
                        $record->mime_type === 'application/pdf' => 'heroicon-o-document-text',
                        default => 'heroicon-o-document',
                    })
                    ->color(fn (Attachment $record) => $record->isImage() ? 'info' : 'gray'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->description(fn (Attachment $record) => $record->original_name !== $record->name
                        ? $record->original_name
                        : null)
                    ->wrap(),

                Tables\Columns\TextColumn::make('origem')
                    ->label('Origem')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => Attachment::origens()[$state] ?? $state)
                    ->color(fn (?string $state) => $state === 'cliente' ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('formatted_size')
                    ->label('Tamanho')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('storage_type')
                    ->label('Onde')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state === 'nas' ? 'NAS' : 'Servidor')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Carregado por')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('origem')
                    ->label('Origem')
                    ->options(Attachment::origens()),
            ])
            ->headerActions([
                Tables\Actions\Action::make('carregar')
                    ->label('Carregar ficheiros')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        Forms\Components\FileUpload::make('ficheiros')
                            ->label('Ficheiros')
                            ->disk('local')
                            ->directory('tmp-anexos')
                            ->multiple()
                            ->reorderable()
                            ->maxSize(51200)
                            ->storeFileNamesIn('nomes_originais')
                            ->required()
                            ->helperText('Fotos, PDFs, folhas de cálculo — até 50 MB cada. No telemóvel dá para tirar a foto na hora.'),

                        Forms\Components\Select::make('origem')
                            ->label('Quem mandou')
                            ->options(Attachment::origens())
                            ->default('cliente')
                            ->required()
                            ->helperText('Daqui a três meses ninguém se lembra se a foto veio do cliente ou foi tirada pela equipa.'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2)
                            ->placeholder('O que é isto e de onde veio.'),
                    ])
                    ->action(function (array $data, AttachmentService $anexos) {
                        $dono = $this->getOwnerRecord();
                        $caminhos = array_values((array) ($data['ficheiros'] ?? []));
                        $nomes = array_values((array) ($data['nomes_originais'] ?? []));
                        $criados = 0;

                        foreach ($caminhos as $indice => $caminho) {
                            $original = $nomes[$indice] ?? basename($caminho);

                            try {
                                $anexos->processUpload(
                                    attachable: $dono,
                                    tempDiskPath: $caminho,
                                    originalName: $original,
                                    name: pathinfo($original, PATHINFO_FILENAME),
                                    origem: $data['origem'] ?? 'cliente',
                                    notes: $data['notes'] ?? null,
                                    uploadedBy: Auth::id(),
                                );

                                $criados++;
                            } catch (\Throwable $e) {
                                // Um ficheiro que falhe nao pode levar os outros
                                // atras: com o NAS em baixo, perdiam-se os cinco
                                // por causa do primeiro.
                                Notification::make()
                                    ->danger()
                                    ->title("Não consegui guardar {$original}")
                                    ->body($e->getMessage())
                                    ->persistent()
                                    ->send();
                            }
                        }

                        if ($criados > 0) {
                            Notification::make()
                                ->success()
                                ->title($criados === 1 ? 'Ficheiro guardado' : "{$criados} ficheiros guardados")
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('abrir')
                    ->label('Abrir')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (Attachment $record) => $record->isPreviewable())
                    ->url(fn (Attachment $record) => route('anexos.ver', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (Attachment $record) => route('anexos.download', $record)),

                Tables\Actions\EditAction::make()->label('Renomear'),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
