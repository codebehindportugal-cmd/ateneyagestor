{{-- Cofre pessoal: criar / abrir / trancar / mudar a master password.
     Sem classes inventadas: este projecto usa o CSS já compilado do Filament,
     por isso o que for fora do comum vai em style= para não depender de
     utilitários do Tailwind que não estão lá. --}}
<x-filament-panels::page>

    @if ($this->codigoParaMostrar)
        <x-filament::section>
            <x-slot name="heading">Código de recuperação</x-slot>
            <x-slot name="description">
                Aparece uma única vez. Escreve-o num papel ou guarda-o fora deste computador —
                é o que te deixa voltar a entrar se perderes a master password.
            </x-slot>

            <div
                style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 1.05rem; letter-spacing: .06em; padding: 1rem; border: 1px dashed rgb(148 163 184); border-radius: .5rem; word-break: break-all;"
                x-data="{ codigo: @js($this->codigoParaMostrar), copiado: false }"
            >
                <span x-text="codigo"></span>

                <div style="margin-top: .75rem;">
                    <x-filament::button
                        size="sm"
                        color="gray"
                        x-on:click="navigator.clipboard.writeText(codigo); copiado = true; setTimeout(() => copiado = false, 2000)"
                    >
                        <span x-text="copiado ? 'Copiado' : 'Copiar código'"></span>
                    </x-filament::button>

                    <x-filament::button size="sm" color="gray" wire:click="esconderCodigo">
                        Já guardei
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endif

    @if (! $this->temCofre())

        {{-- Ainda não há cofre: criar. --}}
        <x-filament::section>
            <x-slot name="heading">Criar o teu cofre</x-slot>
            <x-slot name="description">
                As senhas privadas ficam cifradas com uma chave que só a tua master password abre.
                Nem o administrador do painel, nem quem tenha acesso à base de dados, as consegue ler.
            </x-slot>

            <form wire:submit="criar">
                {{ $this->formCriar }}

                <div style="margin-top: 1rem;">
                    <x-filament::button type="submit">Criar cofre</x-filament::button>
                </div>
            </form>
        </x-filament::section>

    @elseif (! $this->estaDestrancado())

        {{-- Cofre existe, está trancado. --}}
        <x-filament::section>
            <x-slot name="heading">Abrir o cofre</x-slot>
            <x-slot name="description">
                Tranca-se sozinho ao fim de {{ config('cofre.minutos_inactividade', 15) }} minutos sem ser usado.
            </x-slot>

            <form wire:submit="desbloquear">
                {{ $this->formDesbloquear }}

                <div style="margin-top: 1rem;">
                    <x-filament::button type="submit">Abrir</x-filament::button>
                </div>
            </form>

            <div style="margin-top: 1.25rem;">
                <x-filament::link tag="button" wire:click="alternarRecuperacao" color="gray">
                    Perdi a master password
                </x-filament::link>
            </div>

            @if ($this->mostrarRecuperacao)
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgb(226 232 240);">
                    <p class="text-sm text-gray-600" style="margin-bottom: .75rem;">
                        Com o código de recuperação entras e defines já uma master password nova.
                        O código é gasto nesse momento e sai um novo.
                    </p>

                    <form wire:submit="recuperar">
                        {{ $this->formRecuperar }}

                        <div style="margin-top: 1rem;">
                            <x-filament::button type="submit" color="warning">Recuperar cofre</x-filament::button>
                        </div>
                    </form>
                </div>
            @endif
        </x-filament::section>

    @else

        {{-- Cofre aberto. --}}
        <x-filament::section>
            <x-slot name="heading">Cofre aberto</x-slot>
            <x-slot name="description">
                {{ $this->totalEntradas() }} {{ \Illuminate\Support\Str::plural('senha', $this->totalEntradas()) }} guardadas.
                Tranca-se sozinho daqui a {{ $this->minutosRestantes() }} min sem actividade.
            </x-slot>

            <div style="display: flex; gap: .5rem; flex-wrap: wrap;">
                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Admin\Resources\VaultEntryResource::getUrl() }}"
                    icon="heroicon-m-key"
                >
                    Ver as minhas senhas
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Admin\Pages\ImportarKeePass::getUrl() }}"
                    color="gray"
                    icon="heroicon-m-arrow-down-tray"
                >
                    Importar do KeePass
                </x-filament::button>

                <x-filament::button color="danger" wire:click="trancar" icon="heroicon-m-lock-closed">
                    Trancar agora
                </x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section collapsible collapsed>
            <x-slot name="heading">Mudar a master password</x-slot>
            <x-slot name="description">
                A chave do cofre é a mesma — só muda o cadeado. As senhas guardadas não são tocadas.
            </x-slot>

            <form wire:submit="mudarMaster">
                {{ $this->formMudar }}

                <div style="margin-top: 1rem;">
                    <x-filament::button type="submit">Mudar</x-filament::button>
                </div>
            </form>
        </x-filament::section>

        <x-filament::section collapsible collapsed>
            <x-slot name="heading">Código de recuperação</x-slot>
            <x-slot name="description">
                @if ($this->getCofre()?->recuperacao_criada_em)
                    O actual foi gerado {{ $this->getCofre()->recuperacao_criada_em->diffForHumans() }}.
                @endif
                Gerar um novo invalida o anterior.
            </x-slot>

            <x-filament::button color="gray" wire:click="gerarNovoCodigo">
                Gerar código novo
            </x-filament::button>
        </x-filament::section>

    @endif

</x-filament-panels::page>
