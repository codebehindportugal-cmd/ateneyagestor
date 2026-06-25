<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Models\ClientDocument;
use App\Services\ClientDocumentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documentos';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nome / Descrição')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\Select::make('type')
                ->label('Tipo')
                ->options(ClientDocument::types())
                ->default('outro')
                ->required(),

            Forms\Components\FileUpload::make('file_path')
                ->label('Ficheiro')
                ->disk('local')
                ->directory('tmp-client-docs')
                ->storeFileNamesIn('original_name')
                ->acceptedFileTypes([
                    'application/pdf',
                    'image/jpeg', 'image/png', 'image/webp',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])
                ->maxSize(51200) // 50 MB
                ->required()
                ->columnSpanFull()
                ->helperText('PDF, imagem, Word ou Excel — máx. 50 MB.'),
        ]);
    }

    public function table(Table $table): Table
    {
        $manager = $this;

        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ClientDocument::types()[$state] ?? $state)
                    ->color(fn (string $state) => ClientDocument::typeColors()[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('original_name')
                    ->label('Ficheiro')
                    ->limit(30)
                    ->color('gray')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('formatted_size')
                    ->label('Tamanho')
                    ->state(fn (ClientDocument $r) => $r->formatted_size),

                Tables\Columns\TextColumn::make('storage_type')
                    ->label('Armazenamento')
                    ->badge()
                    ->formatStateUsing(fn (string $s) => strtoupper($s))
                    ->color(fn (string $s) => $s === 'nas' ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Carregado por')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Carregar documento')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->modalHeading('Carregar novo documento')
                    ->using(function (array $data) use ($manager): ClientDocument {
                        $client = $manager->getOwnerRecord();

                        return app(ClientDocumentService::class)->processUpload(
                            client: $client,
                            tempDiskPath: (string) ($data['file_path'] ?? ''),
                            originalName: (string) ($data['original_name'] ?? basename((string) ($data['file_path'] ?? ''))),
                            type: $data['type'],
                            documentName: $data['name'],
                            uploadedBy: auth()->id(),
                        );
                    })
                    ->successNotificationTitle('Documento carregado com sucesso'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (ClientDocument $r) => route('admin.client-documents.show', ['document' => $r->id, 'inline' => '1']))
                    ->openUrlInNewTab()
                    ->visible(fn (ClientDocument $r) => $r->isPreviewable()),

                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn (ClientDocument $r) => route('admin.client-documents.show', $r->id))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('delete_doc')
                    ->label('Apagar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Apagar documento')
                    ->modalDescription('O ficheiro será eliminado do armazenamento permanentemente. Esta acção não pode ser revertida.')
                    ->action(function (ClientDocument $record): void {
                        app(ClientDocumentService::class)->delete($record);

                        Notification::make()
                            ->title('Documento eliminado')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }
}
