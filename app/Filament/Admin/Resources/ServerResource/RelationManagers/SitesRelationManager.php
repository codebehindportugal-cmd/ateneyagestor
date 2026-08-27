<?php

namespace App\Filament\Admin\Resources\ServerResource\RelationManagers;

use App\Enums\ServerType;
use App\Filament\Admin\Resources\SiteResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Os sites alojados nesta máquina. Acrescentar um domínio passa a ser uma
 * linha aqui dentro, em vez de um servidor novo com a ligação toda repetida.
 */
class SitesRelationManager extends RelationManager
{
    protected static string $relationship = 'sites';

    protected static ?string $title = 'Sites alojados';

    protected static ?string $modelLabel = 'site';

    protected static ?string $pluralModelLabel = 'sites';

    public function form(Form $form): Form
    {
        // O servidor já é este, por isso o campo não aparece.
        return $form->schema(SiteResource::formSchema(withServer: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Site')
                    ->description(fn ($record) => $record->domain)
                    ->searchable(),
                Tables\Columns\TextColumn::make('client.name')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('type')->label('Tipo')->badge(),
                Tables\Columns\TextColumn::make('wp_root')
                    ->label('Caminho')
                    ->formatStateUsing(fn ($state, $record) => $state ?: $record->app_path ?: $record->domain)
                    ->limit(45)
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('latestBackupRun.status')
                    ->label('Ultimo backup')
                    ->badge()
                    ->color(fn ($state) => $state?->color() ?? 'gray')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? 'Sem dados'),
                Tables\Columns\IconColumn::make('is_active')->label('Ativo')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('Tipo')->options([
                    ServerType::WordPress->value  => ServerType::WordPress->label(),
                    ServerType::VpsLaravel->value => ServerType::VpsLaravel->label(),
                    ServerType::Plesk->value      => ServerType::Plesk->label(),
                    ServerType::Cpanel->value     => ServerType::Cpanel->label(),
                ]),
                Tables\Filters\TernaryFilter::make('is_active')->label('Ativo'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Acrescentar site'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
