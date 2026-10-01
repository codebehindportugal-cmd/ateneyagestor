<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Vault;
use App\Models\VaultEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Para quem perdeu a master password E o código de recuperação.
 *
 * Não há forma de abrir o cofre sem um dos dois — foi feito assim de propósito.
 * O que este comando faz é tirar o cofre do caminho para a pessoa poder criar
 * um novo na página "Cofre pessoal":
 *
 *  1. Guarda o cofre e as entradas, tal como estão (cifrados), num ficheiro em
 *     storage/app/cofre-arquivo/. Se um dia aparecer a senha ou o código, as
 *     senhas antigas ainda se recuperam a partir daí.
 *  2. Apaga o cofre e as entradas dessa pessoa da base de dados.
 *
 * Nada é decifrado nem fica em claro em lado nenhum.
 */
class CofreRepor extends Command
{
    protected $signature = 'cofre:repor {email : Email do utilizador cujo cofre se vai repor}';

    protected $description = 'Arquiva o cofre pessoal de um utilizador (cifrado) e apaga-o, para poder criar um novo';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Nao ha nenhum utilizador com esse email.');

            return self::FAILURE;
        }

        $cofre = Vault::where('user_id', $user->id)->first();

        if (! $cofre) {
            $this->warn('Este utilizador nao tem cofre. Ja pode criar um na pagina "Cofre pessoal".');

            return self::SUCCESS;
        }

        $entradas = VaultEntry::where('user_id', $user->id)->get();

        $this->line("Utilizador: {$user->name} <{$user->email}>");
        $this->line('Entradas no cofre: ' . $entradas->count());
        $this->newLine();
        $this->warn('Sem a master password ou o codigo de recuperacao estas senhas NAO se conseguem ler.');
        $this->warn('Ficam guardadas (cifradas) num ficheiro de arquivo e saem da base de dados.');

        if (! $this->confirm('Repor o cofre?', false)) {
            $this->line('Nada foi feito.');

            return self::SUCCESS;
        }

        // makeVisible: o modelo esconde estes campos de toArray(), e aqui
        // queremos o embrulho completo para se poder recuperar mais tarde.
        $arquivo = [
            'arquivado_em' => now()->toIso8601String(),
            'user_id'      => $user->id,
            'email'        => $user->email,
            'nota'         => 'Cofre arquivado por cofre:repor. Tudo cifrado; abre-se com a master password ou o codigo de recuperacao antigos.',
            'vault'        => $cofre->makeVisible(['salt', 'chave_embrulhada', 'salt_recuperacao', 'chave_embrulhada_recuperacao'])->toArray(),
            'entries'      => $entradas->each->makeVisible(['segredo', 'notas'])->toArray(),
        ];

        $caminho = sprintf('cofre-arquivo/user-%d-%s.json', $user->id, now()->format('Ymd-His'));

        Storage::disk('local')->put($caminho, json_encode($arquivo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if (! Storage::disk('local')->exists($caminho)) {
            $this->error('Nao consegui escrever o arquivo. Nada foi apagado.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($user, $cofre) {
            VaultEntry::where('user_id', $user->id)->delete();
            $cofre->delete();
        });

        $this->info('Cofre reposto.');
        $this->line('Arquivo: ' . Storage::disk('local')->path($caminho));
        $this->newLine();
        $this->line('Agora: entra no painel > Cofre pessoal > cria a master password nova,');
        $this->line('guarda o codigo de recuperacao FORA do cofre e volta a importar o .kdbx.');

        return self::SUCCESS;
    }
}
