<?php

namespace App\Filament\Admin\Pages;

use App\Models\AccountingDocument;
use App\Models\Brand;
use App\Models\MoloniDocumento;
use App\Models\Setting;
use App\Services\Contabilidade\Resultados;
use App\Services\Moloni\MoloniClient;
use App\Services\Moloni\MoloniConfig;
use App\Services\Moloni\MoloniException;
use App\Services\Moloni\SincronizadorMoloni;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

/**
 * Vendas do Moloni contra as despesas do painel, mes a mes.
 *
 * As vendas vem da copia local (moloni_documentos), refrescada pelo
 * agendador e pelo botao "Sincronizar Moloni". A marca de cada venda sai da
 * serie do documento, pelo mapa "Series e marcas".
 */
class ResultadosPage extends Page
{
    /** Numeros da empresa: so' o administrador. */
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() === true;
    }

    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Resultados';
    protected static ?string $navigationGroup = 'Contabilidade';
    protected static ?int    $navigationSort  = 3;
    protected static ?string $slug            = 'resultados';
    protected static string  $view            = 'filament.pages.resultados';

    public int $ano;

    /** 'todas', 'sem' ou o id da marca. String por causa do <select>. */
    public string $marca = 'todas';

    public function mount(): void
    {
        $this->ano = (int) now()->year;
    }

    public function getTitle(): string
    {
        return 'Resultados';
    }

    public function getViewData(): array
    {
        $filtro = match ($this->marca) {
            'todas' => null,
            'sem'   => 0,
            default => (int) $this->marca,
        };

        $mapa = MoloniDocumento::mapaSeries();
        $series = MoloniDocumento::seriesConhecidas();
        $ultima = Setting::get(SincronizadorMoloni::CHAVE_ULTIMA);

        return [
            'r'              => Resultados::doAno($this->ano, $filtro),
            'anos'           => $this->anosDisponiveis(),
            'marcas'         => Brand::selectOptions(),
            'categorias'     => AccountingDocument::categories(),
            'meses'          => array_map(fn ($m) => AccountingDocument::monthName($m), range(1, 12)),
            'moloniLigado'   => MoloniConfig::ligado() && MoloniClient::daConfig()->configurado(),
            'ultimaSync'     => $ultima ? Carbon::parse($ultima)->timezone('Europe/Lisbon') : null,
            'seriesSemMarca' => array_diff_key($series, $mapa),
        ];
    }

    /** @return array<int,int> */
    private function anosDisponiveis(): array
    {
        $anos = collect([(int) now()->year])
            ->merge(MoloniDocumento::query()->distinct()->pluck('ano'))
            ->merge(AccountingDocument::query()->whereNotNull('year')->distinct()->pluck('year'))
            ->map(fn ($a) => (int) $a)
            ->filter(fn ($a) => $a > 2000)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return array_combine($anos, $anos);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sincronizar')
                ->label('Sincronizar Moloni')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    try {
                        $r = (new SincronizadorMoloni(MoloniClient::daConfig()))->ano($this->ano);

                        Notification::make()
                            ->title("Moloni {$r['ano']}: {$r['gravados']} documentos")
                            ->body($r['removidos'] > 0 ? "{$r['removidos']} que já não estão no Moloni foram retirados." : null)
                            ->success()
                            ->send();
                    } catch (MoloniException $e) {
                        Notification::make()
                            ->title('Não consegui ler o Moloni')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            Action::make('series')
                ->label('Séries e marcas')
                ->icon('heroicon-o-tag')
                ->color('gray')
                ->modalHeading('A que marca pertence cada série do Moloni')
                ->modalDescription('As vendas de uma série sem marca aparecem em "Sem marca". Mudar aqui muda também os meses anteriores.')
                ->modalSubmitActionLabel('Guardar')
                ->fillForm(function () {
                    $dados = [];
                    foreach (MoloniDocumento::mapaSeries() as $serie => $marca) {
                        $dados["s{$serie}"] = $marca;
                    }

                    return $dados;
                })
                ->form(function () {
                    $series = $this->seriesParaMapa();

                    if ($series === []) {
                        return [
                            Forms\Components\Placeholder::make('nada')
                                ->label('')
                                ->content('Ainda não há séries. Sincroniza o Moloni primeiro.'),
                        ];
                    }

                    return collect($series)
                        ->map(fn (string $nome, int $id) => Forms\Components\Select::make("s{$id}")
                            ->label($nome)
                            ->options(Brand::selectOptions())
                            ->placeholder('Sem marca'))
                        ->values()
                        ->all();
                })
                ->action(function (array $data) {
                    $mapa = [];
                    foreach ($data as $campo => $marca) {
                        if (str_starts_with($campo, 's')) {
                            $mapa[(int) substr($campo, 1)] = $marca ? (int) $marca : null;
                        }
                    }

                    MoloniDocumento::guardarMapaSeries($mapa);

                    Notification::make()->title('Séries guardadas')->success()->send();
                }),
        ];
    }

    /**
     * As series que ja apareceram nas vendas e, se o Moloni responder, as
     * outras que la existem — para se poder atribuir a marca antes da
     * primeira fatura dessa serie.
     *
     * @return array<int,string>
     */
    private function seriesParaMapa(): array
    {
        $series = MoloniDocumento::seriesConhecidas();

        $cliente = MoloniClient::daConfig();
        if (MoloniConfig::ligado() && $cliente->configurado()) {
            try {
                foreach ($cliente->series($cliente->empresaId()) as $s) {
                    $id = (int) ($s['document_set_id'] ?? 0);
                    if ($id > 0 && ! isset($series[$id])) {
                        $series[$id] = (string) ($s['name'] ?? "Série {$id}");
                    }
                }
            } catch (MoloniException) {
                // Sem Moloni fica-se pelas que ja' se conhecem.
            }
        }

        asort($series);

        return $series;
    }
}
