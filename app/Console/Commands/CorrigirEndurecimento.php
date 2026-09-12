<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\Seguranca\AuditoriaEndurecimento;
use App\Services\Seguranca\CatalogoEndurecimento;
use Illuminate\Console\Command;

/**
 * Aplica as correcções de endurecimento em lote, em vez de carregar num botão
 * de cada vez no painel.
 *
 * Por omissão só corre o conjunto seguro: correcções que tiram permissões a
 * mais ou instalam coisas, e que não te podem fechar o acesso à máquina. As
 * que mexem no SSH e na firewall ficam de fora e só entram se as pedires pelo
 * nome — são as que, se a máquina não estiver como se pensa, te deixam do
 * lado de fora da porta.
 *
 *   php artisan seguranca:corrigir --todos
 *   php artisan seguranca:corrigir contabo-a --so=fail2ban
 *   php artisan seguranca:corrigir --todos --incluir=ssh_root,ssh_senha --excepto=carina
 *   php artisan seguranca:corrigir --todos --simular
 */
class CorrigirEndurecimento extends Command
{
    protected $signature = 'seguranca:corrigir
                            {servidor? : Id ou nome do servidor}
                            {--todos : Em todos os servidores activos}
                            {--so= : Só estas verificações (lista separada por vírgulas)}
                            {--incluir= : Acrescentar ao conjunto seguro (ex: ssh_root,ssh_senha,firewall)}
                            {--excepto= : Saltar estes servidores (nomes separados por vírgulas)}
                            {--simular : Mostrar o que faria, sem correr nada}
                            {--forcar : Não perguntar antes de começar}';

    protected $description = 'Aplica as correcções de endurecimento nos servidores';

    /**
     * O que se aplica sem pensar duas vezes: nada aqui corta acessos.
     */
    private const SEGURAS = [
        'wp_config',
        'fail2ban',
        'apache_listagem',
        'apache_assinatura',
        'apache_default',
        'updates_automaticos',
        'escrita_todos',
    ];

    /**
     * O que fica de fora por omissão, e porquê — é isto que se mostra a quem
     * as pedir.
     */
    private const CUIDADO = [
        'ssh_root'         => 'passa a só entrar por chave; confirma que a tua chave entra nessa máquina',
        'ssh_senha'        => 'desliga a senha no SSH; sem chave tua na máquina, ficas fechado de fora',
        'firewall'         => 'activa a ufw; portas fora de 22/80/443 deixam de responder',
        'mysql_exposto'    => 'prende o MySQL ao localhost e reinicia-o',
        'updates_pendentes' => 'instala actualizações e pode reiniciar serviços',
    ];

    public function handle(AuditoriaEndurecimento $auditoria): int
    {
        $servidores = $this->servidores();

        if ($servidores->isEmpty()) {
            $this->error('Não encontrei servidores para tratar.');

            return self::FAILURE;
        }

        $chaves = $this->chaves();

        if ($chaves === []) {
            $this->error('Nenhuma verificação escolhida.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Servidores: <info>'.$servidores->pluck('name')->implode(', ').'</info>');
        $this->line('Correcções: <info>'.implode(', ', $chaves).'</info>');

        foreach ($chaves as $chave) {
            if (isset(self::CUIDADO[$chave])) {
                $this->warn("  ! {$chave} — ".self::CUIDADO[$chave]);
            }
        }

        $this->newLine();

        if ($this->option('simular')) {
            $this->comment('Modo simulação: nada vai ser executado.');
        } elseif (! $this->option('forcar') && ! $this->confirm('Avançar?', true)) {
            return self::SUCCESS;
        }

        $feitas = 0;
        $saltadas = 0;
        $falhadas = 0;

        foreach ($servidores as $servidor) {
            $this->newLine();
            $this->info("── {$servidor->name} ({$servidor->host})");

            $catalogo = CatalogoEndurecimento::para($servidor);

            foreach ($chaves as $chave) {
                $verificacao = $catalogo[$chave] ?? null;

                if (! $verificacao) {
                    continue; // não se aplica a esta máquina (ex: Apache em Plesk)
                }

                if (! $verificacao->correcao) {
                    continue; // é só para ver
                }

                // Ver primeiro como está: não se mexe no que já está bem.
                try {
                    $antes = $auditoria->reverificar($servidor, $verificacao);
                } catch (\Throwable $e) {
                    $this->line("   <fg=red>erro</>  {$verificacao->label}: ".$e->getMessage());
                    $falhadas++;

                    continue;
                }

                if (in_array($antes['estado'], ['ok', 'info'], true)) {
                    $this->line("   <fg=gray>—</>     {$verificacao->label} (já estava bem)");
                    $saltadas++;

                    continue;
                }

                if ($this->option('simular')) {
                    $this->line("   <fg=yellow>faria</> {$verificacao->label}");
                    $saltadas++;

                    continue;
                }

                try {
                    $r = $auditoria->corrigir($servidor, $chave);
                } catch (\Throwable $e) {
                    $this->line("   <fg=red>ERRO</>  {$verificacao->label}: ".$e->getMessage());
                    $falhadas++;

                    continue;
                }

                if ($r['resultado']['estado'] === 'ok') {
                    $this->line("   <fg=green>ok</>    {$verificacao->label}");
                    $feitas++;
                } else {
                    $this->line("   <fg=yellow>meio</>  {$verificacao->label} — correu mas continua: {$r['resultado']['detalhe']}");
                    $falhadas++;
                }
            }
        }

        $this->newLine();
        $this->line("Feitas: {$feitas} · já estavam bem ou saltadas: {$saltadas} · por resolver: {$falhadas}");
        $this->comment('Corre `php artisan seguranca:auditar --todos` para o retrato ficar actualizado no painel.');

        return self::SUCCESS;
    }

    private function servidores()
    {
        $excepto = collect(explode(',', (string) $this->option('excepto')))
            ->map(fn ($n) => trim($n))
            ->filter()
            ->all();

        $query = $this->option('todos') || ! $this->argument('servidor')
            ? Server::where('is_active', true)
            : Server::where('id', $this->argument('servidor'))->orWhere('name', $this->argument('servidor'));

        return $query->orderBy('name')->get()
            ->reject(fn (Server $s) => in_array($s->name, $excepto, true))
            ->values();
    }

    /** @return array<int, string> */
    private function chaves(): array
    {
        if ($so = $this->option('so')) {
            return collect(explode(',', $so))->map(fn ($c) => trim($c))->filter()->values()->all();
        }

        $chaves = self::SEGURAS;

        if ($incluir = $this->option('incluir')) {
            foreach (explode(',', $incluir) as $c) {
                $c = trim($c);

                if ($c !== '' && ! in_array($c, $chaves, true)) {
                    $chaves[] = $c;
                }
            }
        }

        return $chaves;
    }
}
