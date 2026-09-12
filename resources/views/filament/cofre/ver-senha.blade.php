{{-- O único sítio onde uma senha privada aparece decifrada. Fica dentro do
     modal, por pedido, e não na listagem. --}}
<div x-data="{ visivel: false, copiado: null, senha: @js($senha), utilizador: @js($entrada->utilizador) }" style="display: grid; gap: 1rem;">

    @if (filled($entrada->utilizador))
        <div>
            <div class="text-sm text-gray-500">Utilizador</div>
            <div style="display: flex; align-items: center; gap: .5rem;">
                <span style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; word-break: break-all;">{{ $entrada->utilizador }}</span>
                <x-filament::icon-button
                    icon="heroicon-m-clipboard"
                    color="gray"
                    size="sm"
                    label="Copiar utilizador"
                    x-on:click="navigator.clipboard.writeText(utilizador); copiado = 'utilizador'; setTimeout(() => copiado = null, 2000)"
                />
                <span class="text-sm text-gray-500" x-show="copiado === 'utilizador'" x-cloak>copiado</span>
            </div>
        </div>
    @endif

    <div>
        <div class="text-sm text-gray-500">Senha</div>
        <div style="display: flex; align-items: center; gap: .5rem;">
            <span
                style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; word-break: break-all;"
                x-text="visivel ? senha : '•'.repeat(Math.min(senha.length, 24))"
            ></span>

            <x-filament::icon-button
                icon="heroicon-m-eye"
                color="gray"
                size="sm"
                label="Mostrar ou esconder"
                x-on:click="visivel = ! visivel"
            />

            <x-filament::icon-button
                icon="heroicon-m-clipboard"
                color="gray"
                size="sm"
                label="Copiar senha"
                x-on:click="navigator.clipboard.writeText(senha); copiado = 'senha'; setTimeout(() => { copiado = null; navigator.clipboard.writeText(''); }, 30000)"
            />

            <span class="text-sm text-gray-500" x-show="copiado === 'senha'" x-cloak>
                copiada — a área de transferência limpa-se em 30s
            </span>
        </div>
    </div>

    @if (filled($entrada->url))
        <div>
            <div class="text-sm text-gray-500">Endereço</div>
            <a href="{{ $entrada->url }}" target="_blank" rel="noopener" class="text-sm">{{ $entrada->url }}</a>
        </div>
    @endif

    @if (filled($notas))
        <div>
            <div class="text-sm text-gray-500">Notas</div>
            <div class="text-sm" style="white-space: pre-wrap;">{{ $notas }}</div>
        </div>
    @endif

</div>
