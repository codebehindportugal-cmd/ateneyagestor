<?php

namespace App\Filament\Admin\Pages;

use App\Services\Moloni\MoloniClient;
use App\Services\Moloni\MoloniConfig;
use App\Services\Moloni\MoloniException;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

/**
 * Contabilidade > Moloni (01/10/2026): as credenciais da API do Moloni, que
 * antes só se punham no .env do servidor. Ver App\Services\Moloni\MoloniConfig.
 *
 * Os segredos (client secret e password) nunca voltam ao ecrã: a caixa vem
 * vazia e diz se já há um guardado. Deixar em branco mantém o que lá está.
 */
class MoloniPage extends Page implements HasForms
{
    use InteractsWithForms;

    /** Só o administrador. */
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() === true;
    }

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Moloni';

    protected static ?string $navigationGroup = 'Contabilidade';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'moloni';

    protected static string $view = 'filament.admin.pages.moloni';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Ligação ao Moloni';
    }

    public function mount(): void
    {
        $config = MoloniConfig::todas();

        $this->form->fill([
            'enabled'       => (bool) ($config['enabled'] ?? false),
            'client_id'     => MoloniConfig::lerGuardado('client_id'),
            'username'      => MoloniConfig::lerGuardado('username'),
            'company_id'    => MoloniConfig::lerGuardado('company_id'),
            'client_secret' => null,
            'password'      => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('Ligação')
                    ->description('Só leitura: o painel vai buscar as faturas fechadas para os Resultados. Os campos vazios usam o que estiver no .env do servidor.')
                    ->schema([
                        Forms\Components\Toggle::make('enabled')
                            ->label('Sincronizar com o Moloni')
                            ->helperText('De 2 em 2 horas, e no botão "Sincronizar Moloni" dos Resultados.'),
                    ]),

                Forms\Components\Section::make('Aplicação de programador')
                    ->description('Em moloni.pt/dev — o client id e o client secret da aplicação.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('client_id')
                            ->label('Client ID')
                            ->autocomplete('off')
                            ->helperText(fn () => $this->origemTexto('client_id')),
                        $this->segredo('client_secret', 'Client secret'),
                    ]),

                Forms\Components\Section::make('Conta')
                    ->description('Um utilizador do Moloni com acesso à empresa. Basta ser de leitura.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->label('Utilizador (email)')
                            ->autocomplete('off')
                            ->helperText(fn () => $this->origemTexto('username')),
                        $this->segredo('password', 'Password'),
                        Forms\Components\TextInput::make('company_id')
                            ->label('ID da empresa')
                            ->numeric()
                            ->helperText('Vazio = a primeira empresa da conta. "Testar ligação" mostra o id de cada uma.'),
                    ]),
            ]);
    }

    private function segredo(string $campo, string $rotulo): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make($campo)
            ->label($rotulo)
            ->password()
            ->revealable()
            ->autocomplete('new-password')
            ->placeholder(fn () => MoloniConfig::origem($campo) ? '•••••••• (guardado — deixa vazio para manter)' : '')
            ->helperText(fn () => $this->origemTexto($campo))
            ->hintAction(
                Forms\Components\Actions\Action::make("esquecer_{$campo}")
                    ->label('Apagar')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Apaga o valor guardado no painel. Se houver um no .env, passa a valer esse.')
                    ->visible(fn () => MoloniConfig::origem($campo) === 'pagina')
                    ->action(function () use ($campo) {
                        MoloniConfig::esquecer($campo);
                        Notification::make()->title('Apagado')->success()->send();
                    }),
            );
    }

    private function origemTexto(string $campo): string
    {
        return match (MoloniConfig::origem($campo)) {
            'pagina' => 'Guardado aqui no painel.',
            'env'    => 'A usar o valor do .env do servidor.',
            default  => 'Por preencher.',
        };
    }

    public function guardar(): void
    {
        $dados = $this->form->getState();

        MoloniConfig::guardar((bool) ($dados['enabled'] ?? false), $dados);

        $this->mount();

        Notification::make()
            ->title('Guardado')
            ->body(MoloniClient::daConfig()->configurado()
                ? 'Experimenta "Testar ligação".'
                : 'Ainda falta preencher algum campo (ou tê-lo no .env).')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testar')
                ->label('Testar ligação')
                ->icon('heroicon-o-signal')
                ->color('gray')
                ->action(function () {
                    $cliente = MoloniClient::daConfig();

                    if (! $cliente->configurado()) {
                        Notification::make()->title('Faltam credenciais')
                            ->body('Preenche e guarda o client id, o client secret, o utilizador e a password.')
                            ->warning()->send();

                        return;
                    }

                    try {
                        $empresas = $cliente->empresas();
                    } catch (MoloniException $e) {
                        Notification::make()->title('O Moloni recusou')->body($e->getMessage())
                            ->danger()->persistent()->send();

                        return;
                    } catch (\Throwable $e) {
                        report($e);
                        Notification::make()->title('Não deu para ligar ao Moloni')->body($e->getMessage())
                            ->danger()->persistent()->send();

                        return;
                    }

                    $lista = collect($empresas)
                        ->map(fn ($e) => e(($e['name'] ?? '—') . ' — id ' . ($e['company_id'] ?? '?')))
                        ->join('<br>');

                    Notification::make()
                        ->title('Ligação a funcionar')
                        ->body($lista !== '' ? "Empresas na conta:<br>{$lista}" : 'A conta não tem empresas.')
                        ->success()->persistent()->send();
                }),

            Action::make('sincronizar')
                ->label('Sincronizar agora')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn () => MoloniConfig::ligado())
                ->requiresConfirmation()
                ->modalDescription('Traz as faturas fechadas do ano para os Resultados. Pode demorar um minuto.')
                ->action(function () {
                    @set_time_limit(0);

                    try {
                        $codigo = Artisan::call('moloni:sincronizar');
                    } catch (\Throwable $e) {
                        report($e);
                        Notification::make()->title('A sincronização falhou')->body($e->getMessage())
                            ->danger()->persistent()->send();

                        return;
                    }

                    Notification::make()
                        ->title($codigo === 0 ? 'Sincronizado' : 'A sincronização acabou com erros')
                        ->body(str(Artisan::output())->trim()->limit(500)->toString())
                        ->color($codigo === 0 ? 'success' : 'warning')
                        ->persistent()->send();
                }),
        ];
    }
}
