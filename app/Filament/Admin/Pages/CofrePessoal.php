<?php

namespace App\Filament\Admin\Pages;

use App\Models\Vault;
use App\Models\VaultEntry;
use App\Services\Cofre\CofreException;
use App\Services\Cofre\CofreSessao;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

/**
 * A porta do cofre pessoal: criar, desbloquear, trancar, mudar a master
 * password e tratar do código de recuperação.
 *
 * As senhas em si vivem no VaultEntryResource ("Senhas privadas"). Esta página
 * só trata da chave.
 */
class CofrePessoal extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'Cofre pessoal';

    protected static ?string $navigationGroup = 'Infraestrutura';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.admin.pages.cofre-pessoal';

    public ?string $title = 'Cofre pessoal';

    public ?array $dadosCriar = [];

    public ?array $dadosDesbloquear = [];

    public ?array $dadosMudar = [];

    public ?array $dadosRecuperar = [];

    /** Só se mostra uma vez, logo a seguir a ser gerado. Nunca fica gravado em claro. */
    public ?string $codigoParaMostrar = null;

    public bool $mostrarRecuperacao = false;

    public function mount(): void
    {
        $this->form('formCriar')->fill();
        $this->form('formDesbloquear')->fill();
        $this->form('formMudar')->fill();
        $this->form('formRecuperar')->fill();
    }

    protected function getForms(): array
    {
        return ['formCriar', 'formDesbloquear', 'formMudar', 'formRecuperar'];
    }

    // ── Estado ───────────────────────────────────────────────────────────────

    public function getCofre(): ?Vault
    {
        return Vault::where('user_id', auth()->id())->first();
    }

    public function temCofre(): bool
    {
        return $this->getCofre() !== null;
    }

    public function estaDestrancado(): bool
    {
        return CofreSessao::destrancado();
    }

    public function minutosRestantes(): int
    {
        return CofreSessao::minutosRestantes();
    }

    public function totalEntradas(): int
    {
        return VaultEntry::minhas()->count();
    }

    // ── Formulários ──────────────────────────────────────────────────────────

    public function formCriar(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('aviso')
                    ->hiddenLabel()
                    ->content(new HtmlString(
                        '<p class="text-sm text-gray-600">A master password não fica guardada em lado nenhum: '
                        .'é ela que abre o cofre e não há forma de a recuperar. '
                        .'Se a perderes, só o código de recuperação — que aparece a seguir — dá para voltar a entrar.</p>'
                    )),
                TextInput::make('master')
                    ->label('Master password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(12)
                    ->helperText('Pelo menos 12 caracteres. Escolhe uma frase que saibas de cor.')
                    ->same('master_confirmation'),
                TextInput::make('master_confirmation')
                    ->label('Repete a master password')
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->statePath('dadosCriar');
    }

    public function formDesbloquear(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('master')
                    ->label('Master password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->autofocus(),
            ])
            ->statePath('dadosDesbloquear');
    }

    public function formMudar(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nova')
                    ->label('Nova master password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(12)
                    ->same('nova_confirmation'),
                TextInput::make('nova_confirmation')
                    ->label('Repete a nova')
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->statePath('dadosMudar');
    }

    public function formRecuperar(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('codigo')
                    ->label('Código de recuperação')
                    ->placeholder('XXXXX-XXXXX-XXXXX-XXXXX-XXXXX-XXXXX-XXXXX-XXXXX')
                    ->required(),
                TextInput::make('nova')
                    ->label('Nova master password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(12)
                    ->same('nova_confirmation'),
                TextInput::make('nova_confirmation')
                    ->label('Repete a nova')
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->statePath('dadosRecuperar');
    }

    // ── Acções ───────────────────────────────────────────────────────────────

    public function criar(): void
    {
        if ($this->temCofre()) {
            return;
        }

        $dados = $this->form('formCriar')->getState();

        [, $chave, $codigo] = Vault::criar(auth()->user(), $dados['master']);

        CofreSessao::destrancar($chave);

        $this->form('formCriar')->fill();
        $this->codigoParaMostrar = $codigo;

        Notification::make()
            ->title('Cofre criado')
            ->body('Guarda o código de recuperação — só aparece agora.')
            ->success()
            ->send();
    }

    public function desbloquear(): void
    {
        $dados = $this->form('formDesbloquear')->getState();
        $cofre = $this->getCofre();

        if (! $cofre) {
            return;
        }

        try {
            CofreSessao::destrancar($cofre->abrirCom($dados['master']));
        } catch (CofreException $e) {
            $this->form('formDesbloquear')->fill();

            Notification::make()
                ->title('Não abriu')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->form('formDesbloquear')->fill();

        Notification::make()->title('Cofre aberto')->success()->send();
    }

    public function trancar(): void
    {
        CofreSessao::trancar();

        $this->codigoParaMostrar = null;

        Notification::make()->title('Cofre trancado')->success()->send();
    }

    public function mudarMaster(): void
    {
        $chave = CofreSessao::chave();
        $cofre = $this->getCofre();

        if (! $chave || ! $cofre) {
            Notification::make()->title('Abre o cofre primeiro')->warning()->send();

            return;
        }

        $dados = $this->form('formMudar')->getState();

        $cofre->mudarMasterPassword($chave, $dados['nova']);

        $this->form('formMudar')->fill();

        Notification::make()
            ->title('Master password mudada')
            ->body('O código de recuperação antigo continua válido. Gera um novo se achares melhor.')
            ->success()
            ->send();
    }

    public function gerarNovoCodigo(): void
    {
        $chave = CofreSessao::chave();
        $cofre = $this->getCofre();

        if (! $chave || ! $cofre) {
            Notification::make()->title('Abre o cofre primeiro')->warning()->send();

            return;
        }

        $this->codigoParaMostrar = $cofre->novoCodigoRecuperacao($chave);

        Notification::make()
            ->title('Código novo gerado')
            ->body('O anterior deixou de servir.')
            ->success()
            ->send();
    }

    public function recuperar(): void
    {
        $cofre = $this->getCofre();

        if (! $cofre) {
            return;
        }

        $dados = $this->form('formRecuperar')->getState();

        try {
            $chave = $cofre->abrirComCodigo($dados['codigo']);
        } catch (CofreException $e) {
            Notification::make()
                ->title('O código não serve')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        // Um código de recuperação é de uma vez: entra-se com ele e define-se
        // logo master password nova, e um código novo a seguir.
        $cofre->mudarMasterPassword($chave, $dados['nova']);
        CofreSessao::destrancar($chave);

        $this->codigoParaMostrar   = $cofre->novoCodigoRecuperacao($chave);
        $this->mostrarRecuperacao  = false;

        $this->form('formRecuperar')->fill();

        Notification::make()
            ->title('Cofre recuperado')
            ->body('Master password nova definida. Guarda o novo código de recuperação.')
            ->success()
            ->send();
    }

    public function esconderCodigo(): void
    {
        $this->codigoParaMostrar = null;
    }

    public function alternarRecuperacao(): void
    {
        $this->mostrarRecuperacao = ! $this->mostrarRecuperacao;
    }
}
