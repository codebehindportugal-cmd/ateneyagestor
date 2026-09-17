<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Volta a cifrar, com a APP_KEY actual, tudo o que esta cifrado na base de dados.
 *
 * Existe por causa de 17/09/2026: o .env de producao esteve num repositorio
 * publico, com a APP_KEY. Trocar a chave sem mais nada deixava ilegiveis as
 * credenciais dos clientes e as passwords dos sincronizadores. O caminho e:
 *
 *   1. APP_KEY=<nova>   e   APP_PREVIOUS_KEYS=<antiga>   no .env
 *   2. php artisan config:cache
 *   3. php artisan seguranca:recifrar              (so conta, nao escreve)
 *   4. php artisan seguranca:recifrar --confirmar  (escreve)
 *   5. tirar a APP_PREVIOUS_KEYS do .env e config:cache outra vez
 *
 * Enquanto a chave antiga estiver em APP_PREVIOUS_KEYS o Laravel ainda consegue
 * ler o que foi cifrado com ela; e isso que permite ler com a antiga e gravar
 * com a nova. O passo 5 e o que torna a chave exposta inutil.
 *
 * Nao passa pelos modelos de proposito: o mutator do Credential e o cast
 * `encrypted` do SyncProject cifram na escrita, e um save() normal nao se
 * aperceberia de que o valor mudou. Aqui le-se e grava-se o texto cifrado.
 */
class RecifrarSegredos extends Command
{
    protected $signature = 'seguranca:recifrar {--confirmar : Gravar. Sem isto so conta o que ha para fazer}';

    protected $description = 'Volta a cifrar os segredos da base de dados com a APP_KEY actual (rotacao da chave)';

    /** tabela => colunas cifradas */
    private const COLUNAS = [
        'credentials' => ['password'],
        'sync_projects' => [
            'phc_api_key',
            'phc_password',
            'wintouch_api_key',
            'wintouch_login_password',
            'woo_consumer_key',
            'woo_consumer_secret',
            'woo_admin_app_password',
            'smtp_password',
        ],
    ];

    public function handle(): int
    {
        $gravar = (bool) $this->option('confirmar');

        if (empty(config('app.previous_keys'))) {
            $this->warn('APP_PREVIOUS_KEYS esta vazio. Se a APP_KEY ja foi trocada, o que foi cifrado com a antiga nao se consegue ler.');
        }

        $totalRecifrados = 0;
        $totalIlegiveis = 0;

        foreach (self::COLUNAS as $tabela => $colunas) {
            $linhas = DB::table($tabela)->select(array_merge(['id'], $colunas))->orderBy('id')->get();

            foreach ($linhas as $linha) {
                $novos = [];

                foreach ($colunas as $coluna) {
                    $cifrado = $linha->{$coluna};

                    if ($cifrado === null || $cifrado === '') {
                        continue;
                    }

                    try {
                        $claro = Crypt::decryptString($cifrado);
                    } catch (DecryptException) {
                        $totalIlegiveis++;
                        $this->error("  {$tabela}#{$linha->id}.{$coluna}: nao se consegue ler com nenhuma das chaves");

                        continue;
                    }

                    $novos[$coluna] = Crypt::encryptString($claro);
                }

                if ($novos === []) {
                    continue;
                }

                $totalRecifrados += count($novos);

                if ($gravar) {
                    DB::table($tabela)->where('id', $linha->id)->update($novos);
                }
            }

            $this->line("{$tabela}: " . $linhas->count() . ' linha(s) vistas');
        }

        $this->newLine();
        $verbo = $gravar ? 'recifrados' : 'por recifrar';
        $this->info("Valores {$verbo}: {$totalRecifrados}   Ilegiveis: {$totalIlegiveis}");

        if (! $gravar) {
            $this->line('Nada foi gravado. Correr outra vez com --confirmar.');
        } elseif ($totalIlegiveis === 0) {
            $this->line('Pronto. Agora tira a APP_PREVIOUS_KEYS do .env e corre php artisan config:cache.');
        } else {
            $this->warn('Ha valores ilegiveis: NAO tires ainda a APP_PREVIOUS_KEYS — ve primeiro de onde vem.');
        }

        return $totalIlegiveis > 0 ? self::FAILURE : self::SUCCESS;
    }
}
