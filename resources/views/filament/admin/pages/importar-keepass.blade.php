{{-- Importação do KeePass.

     O ficheiro .kdbx é aberto aqui no browser: a senha do KeePass nunca sai
     desta página e o ficheiro nunca chega ao servidor. Só as entradas já
     lidas é que seguem, por HTTPS, para serem cifradas com a chave do cofre.

     Os dois scripts vivem em public/js (nada de CDN: é uma página onde
     passam senhas). --}}
<x-filament-panels::page>

    {{-- Os scripts vêm antes do markup de propósito: o Alpine arranca no
         DOMContentLoaded e nessa altura a função já tem de existir. --}}
    <script src="{{ asset('js/argon2-bundled.min.js') }}"></script>
    <script src="{{ asset('js/kdbxweb.min.js') }}"></script>
    <script>
        // O kdbxweb não traz Argon2 (é o que o KeePass 2 usa por omissão nos
        // ficheiros KDBX 4). Liga-se aqui ao argon2-bundled, que já traz o
        // WebAssembly lá dentro. `memory` vem em KiB, que é exactamente o que
        // o argon2-browser quer.
        if (window.kdbxweb && window.argon2) {
            kdbxweb.CryptoEngine.setArgon2Impl(
                async (password, salt, memory, iterations, length, parallelism, type) => {
                    const r = await argon2.hash({
                        pass: new Uint8Array(password),
                        salt: new Uint8Array(salt),
                        time: iterations,
                        mem: memory,
                        hashLen: length,
                        parallelism: parallelism,
                        type: type,
                    });

                    return new Uint8Array(r.hash).buffer;
                }
            );
        }

        function importadorKeePass(urlImportar, csrf, urlLista) {
            return {
                ficheiro: null,
                ficheiroChave: null,
                senha: '',
                entradas: [],
                pastas: [],
                erro: '',
                resultado: '',
                aLer: false,
                aImportar: false,
                ignorarRepetidas: true,

                async ler() {
                    this.erro = '';
                    this.resultado = '';
                    this.entradas = [];

                    if (!this.ficheiro) {
                        this.erro = 'Escolhe primeiro o ficheiro .kdbx.';
                        return;
                    }

                    this.aLer = true;

                    try {
                        const dados = await this.ficheiro.arrayBuffer();
                        const chave = this.ficheiroChave ? await this.ficheiroChave.arrayBuffer() : null;

                        const credenciais = new kdbxweb.Credentials(
                            kdbxweb.ProtectedValue.fromString(this.senha),
                            chave
                        );

                        const db = await kdbxweb.Kdbx.load(dados, credenciais);

                        const lixo = db.meta.recycleBinUuid ? db.meta.recycleBinUuid.id : null;
                        const encontradas = [];
                        const pastas = new Set();

                        const texto = (campo) => {
                            if (campo === undefined || campo === null) return '';
                            return typeof campo === 'string' ? campo : campo.getText();
                        };

                        const percorrer = (grupo, caminho) => {
                            if (lixo && grupo.uuid && grupo.uuid.id === lixo) return;

                            const nome = caminho ? caminho + ' / ' + grupo.name : (grupo.name || '');

                            for (const e of grupo.entries) {
                                const titulo = texto(e.fields.get('Title')).trim();
                                const senha = texto(e.fields.get('Password'));
                                const utilizador = texto(e.fields.get('UserName')).trim();

                                if (!titulo && !senha && !utilizador) continue;

                                encontradas.push({
                                    titulo: titulo || '(sem título)',
                                    utilizador: utilizador,
                                    senha: senha,
                                    url: texto(e.fields.get('URL')).trim(),
                                    notas: texto(e.fields.get('Notes')),
                                    pasta: nome,
                                });

                                if (nome) pastas.add(nome);
                            }

                            for (const sub of grupo.groups) percorrer(sub, nome);
                        };

                        for (const raiz of db.groups) percorrer(raiz, '');

                        this.entradas = encontradas;
                        this.pastas = Array.from(pastas);

                        if (!encontradas.length) {
                            this.erro = 'O ficheiro abriu, mas não tinha entradas.';
                        }
                    } catch (e) {
                        console.error(e);
                        this.erro = (e && e.code === 'InvalidKey')
                            ? 'A senha do KeePass não está certa.'
                            : 'Não deu para abrir o ficheiro: ' + (e && e.message ? e.message : e);
                    } finally {
                        this.aLer = false;
                        this.senha = '';
                    }
                },

                async importar() {
                    this.aImportar = true;
                    this.erro = '';
                    this.resultado = '';

                    try {
                        const resposta = await fetch(urlImportar, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify({
                                entradas: this.entradas,
                                ignorar_repetidas: this.ignorarRepetidas,
                            }),
                        });

                        const corpo = await resposta.json();

                        if (!resposta.ok) {
                            this.erro = corpo.erro || 'A importação falhou.';
                            return;
                        }

                        this.resultado = corpo.importadas + ' importadas, ' + corpo.saltadas + ' repetidas ignoradas.';
                        this.entradas = [];

                        setTimeout(() => { window.location.href = urlLista; }, 1500);
                    } catch (e) {
                        this.erro = 'A importação falhou: ' + (e && e.message ? e.message : e);
                    } finally {
                        this.aImportar = false;
                    }
                },
            };
        }
    </script>

    <x-filament::section>
        <x-slot name="heading">Escolhe o ficheiro</x-slot>
        <x-slot name="description">
            Serve o .kdbx do KeePass 2 (e do KeePassXC). O ficheiro é lido dentro do teu browser —
            nem ele nem a senha do KeePass são enviados para o servidor.
        </x-slot>

        <div
            x-data="importadorKeePass(@js(route('cofre.importar')), @js(csrf_token()), @js(\App\Filament\Admin\Resources\VaultEntryResource::getUrl()))"
            style="display: grid; gap: 1rem;"
        >
            <div>
                <label class="text-sm text-gray-600" for="kdbx">Ficheiro .kdbx</label>
                <input type="file" id="kdbx" accept=".kdbx" x-on:change="ficheiro = $event.target.files[0]"
                       style="display: block; margin-top: .35rem;">
            </div>

            <div>
                <label class="text-sm text-gray-600" for="kdbx-senha">Senha do KeePass</label>
                <div style="max-width: 24rem; margin-top: .35rem;">
                    <x-filament::input.wrapper>
                        <x-filament::input type="password" id="kdbx-senha" x-model="senha" autocomplete="off" />
                    </x-filament::input.wrapper>
                </div>
            </div>

            <div>
                <label class="text-sm text-gray-600" for="kdbx-chave">Ficheiro-chave (só se usares um)</label>
                <input type="file" id="kdbx-chave" x-on:change="ficheiroChave = $event.target.files[0]"
                       style="display: block; margin-top: .35rem;">
            </div>

            <div style="display: flex; gap: .5rem; align-items: center;">
                <x-filament::button x-on:click="ler()" x-bind:disabled="aLer">
                    <span x-text="aLer ? 'A abrir…' : 'Abrir ficheiro'"></span>
                </x-filament::button>
                <span class="text-sm" style="color: rgb(190 18 60);" x-text="erro" x-show="erro" x-cloak></span>
            </div>

            <template x-if="entradas.length">
                <div style="border-top: 1px solid rgb(226 232 240); padding-top: 1rem; display: grid; gap: .75rem;">
                    <div class="text-sm">
                        Encontradas <strong x-text="entradas.length"></strong> entradas
                        em <strong x-text="pastas.length"></strong> pastas.
                    </div>

                    <div style="max-height: 16rem; overflow: auto; border: 1px solid rgb(226 232 240); border-radius: .5rem;">
                        <table style="width: 100%; font-size: .8125rem;">
                            <thead>
                                <tr style="text-align: left; background: rgb(248 250 252);">
                                    <th style="padding: .4rem .6rem;">Título</th>
                                    <th style="padding: .4rem .6rem;">Utilizador</th>
                                    <th style="padding: .4rem .6rem;">Pasta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(e, i) in entradas.slice(0, 200)" :key="i">
                                    <tr style="border-top: 1px solid rgb(241 245 249);">
                                        <td style="padding: .35rem .6rem;" x-text="e.titulo"></td>
                                        <td style="padding: .35rem .6rem;" x-text="e.utilizador || '—'"></td>
                                        <td style="padding: .35rem .6rem;" x-text="e.pasta || '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <label class="text-sm" style="display: flex; align-items: center; gap: .5rem;">
                        <input type="checkbox" x-model="ignorarRepetidas">
                        Não importar as que já tiverem o mesmo título e utilizador
                    </label>

                    <div style="display: flex; gap: .5rem; align-items: center;">
                        <x-filament::button color="success" x-on:click="importar()" x-bind:disabled="aImportar">
                            <span x-text="aImportar ? 'A importar…' : 'Importar para o cofre'"></span>
                        </x-filament::button>
                        <span class="text-sm text-gray-600" x-text="resultado" x-show="resultado" x-cloak></span>
                    </div>
                </div>
            </template>
        </div>
    </x-filament::section>


</x-filament-panels::page>
