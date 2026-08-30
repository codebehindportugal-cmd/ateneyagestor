<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RoutineResource\Pages;
use App\Models\Brand;
use App\Models\Routine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoutineResource extends Resource
{
    protected static ?string $model = Routine::class;
    protected static ?string $navigationIcon  = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationLabel = 'Rotinas & Pagamentos';
    protected static ?string $navigationGroup = 'Operação';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $modelLabel = 'rotina';
    protected static ?string $pluralModelLabel = 'rotinas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('O que é')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('nome')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Renda do escritório, Backup semanal, IVA trimestral')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('tipo')
                        ->label('Tipo')
                        ->options(Routine::tipos())
                        ->default('tarefa')
                        ->required()
                        ->live(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Activa')
                        ->default(true)
                        ->helperText('Desligar pára de gerar datas novas; as que já existem ficam.'),
                ]),

            Forms\Components\Section::make('Quando')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('periodicidade')
                        ->label('Periodicidade')
                        ->options(Routine::periodicidades())
                        ->default('semanal')
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('dia_semana')
                        ->label('Dia da semana')
                        ->options(Routine::diasDaSemana())
                        ->default(1)
                        ->required(fn (Forms\Get $get) => in_array($get('periodicidade'), ['semanal', 'quinzenal'], true))
                        ->visible(fn (Forms\Get $get) => in_array($get('periodicidade'), ['semanal', 'quinzenal'], true)),

                    Forms\Components\TextInput::make('dia_mes')
                        ->label('Dia do mês')
                        ->numeric()->minValue(1)->maxValue(31)->default(1)
                        ->helperText('31 cai no último dia nos meses curtos.')
                        ->required(fn (Forms\Get $get) => ! in_array($get('periodicidade'), ['semanal', 'quinzenal'], true))
                        ->visible(fn (Forms\Get $get) => ! in_array($get('periodicidade'), ['semanal', 'quinzenal'], true)),

                    Forms\Components\Select::make('mes')
                        ->label('Mês')
                        ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => \App\Models\AccountingDocument::monthName($m)])->all())
                        ->visible(fn (Forms\Get $get) => $get('periodicidade') === 'anual')
                        ->required(fn (Forms\Get $get) => $get('periodicidade') === 'anual'),

                    Forms\Components\DatePicker::make('starts_on')
                        ->label('A partir de')
                        ->native(false)->displayFormat('d/m/Y')
                        ->helperText('Também alinha os trimestrais e as quinzenais.'),

                    Forms\Components\DatePicker::make('ends_on')
                        ->label('Até')
                        ->native(false)->displayFormat('d/m/Y')
                        ->helperText('Deixar vazio para não ter fim.'),
                ]),

            Forms\Components\Section::make('Pagamento')
                ->columns(3)
                ->visible(fn (Forms\Get $get) => $get('tipo') === 'pagamento')
                ->schema([
                    Forms\Components\TextInput::make('amount_cents')
                        ->label('Valor')
                        ->numeric()->prefix('€')->step(0.01)->minValue(0)
                        ->afterStateHydrated(fn (Forms\Components\TextInput $c, $state) =>
                            $c->state($state !== null ? number_format($state / 100, 2, '.', '') : null))
                        ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? null : (int) round((float) $state * 100))
                        ->helperText('Copiado para cada ocorrência quando é gerada.'),

                    Forms\Components\TextInput::make('fornecedor')->label('Fornecedor')->maxLength(255),

                    Forms\Components\Select::make('brand_id')
                        ->label('Marca / Empresa')
                        ->options(fn () => Brand::selectOptions())
                        ->searchable(),
                ]),

            Forms\Components\Textarea::make('notas')->label('Notas')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')->label('Rotina')->searchable()->weight('medium'),

                Tables\Columns\TextColumn::make('tipo')->label('Tipo')->badge()
                    ->formatStateUsing(fn (string $state) => Routine::tipos()[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'pagamento' ? 'danger' : 'info'),

                Tables\Columns\TextColumn::make('periodicidade')->label('Quando')
                    ->formatStateUsing(fn (Routine $r) => static::quandoLegivel($r)),

                Tables\Columns\TextColumn::make('amount_cents')->label('Valor')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 2, ',', ' ').' €' : null),

                Tables\Columns\TextColumn::make('fornecedor')->label('Fornecedor')->placeholder('—')->toggleable(),

                Tables\Columns\IconColumn::make('is_active')->label('Activa')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')->label('Tipo')->options(Routine::tipos()),
                Tables\Filters\TernaryFilter::make('is_active')->label('Activa'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make()->label('Apagar'),
            ])
            ->defaultSort('nome')
            ->emptyStateHeading('Sem rotinas')
            ->emptyStateDescription('Acrescenta o que se repete: backups semanais, rendas, avenças, impostos.');
    }

    /** "Todas as segundas", "Dia 5 de cada mês" — em vez do valor cru. */
    public static function quandoLegivel(Routine $r): string
    {
        $dia = Routine::diasDaSemana()[$r->dia_semana] ?? null;

        return match ($r->periodicidade) {
            'semanal'    => $dia ? "Todas as {$dia}s" : 'Semanal',
            'quinzenal'  => $dia ? "De 2 em 2 semanas, à {$dia}" : 'De 2 em 2 semanas',
            'mensal'     => 'Dia '.($r->dia_mes ?: 1).' de cada mês',
            'trimestral' => 'Dia '.($r->dia_mes ?: 1).', de 3 em 3 meses',
            'semestral'  => 'Dia '.($r->dia_mes ?: 1).', de 6 em 6 meses',
            'anual'      => 'Dia '.($r->dia_mes ?: 1).' de '.\App\Models\AccountingDocument::monthName((int) ($r->mes ?: 1)),
            default      => (string) $r->periodicidade,
        };
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoutines::route('/'),
            'create' => Pages\CreateRoutine::route('/create'),
            'edit'   => Pages\EditRoutine::route('/{record}/edit'),
        ];
    }
}
