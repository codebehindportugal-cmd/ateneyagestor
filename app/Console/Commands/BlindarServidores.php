<?php

namespace App\Console\Commands;

use App\Filament\Admin\Resources\HardeningAuditResource;
use App\Models\Server;
use App\Services\Seguranca\AuditoriaEndurecimento;
use App\Services\Seguranca\CatalogoEndurecimento;
use App\Services\SshService;
use Illuminate\Console\Command;

/**
 * Uma linha só: audita, corrige tudo o que houver para corrigir, e volta a
 * auditar para o painel ficar com o retrato certo.
 *
 *   php artisan seguranca:blindar
 *   php artisan seguranca:blindar --simular
 *   php artisan seguranca:blindar contabo-a
 *
 * A diferença para o `seguranca:corrigir` é que aqui as decisões perigosas
 * não ficam do lado de quem escreve o comando: o próprio servidor é
 * interrogado antes, e cada correcção de risco só entra se a máquina provar
 * que não fica inacessível. Em concreto:
 *
 *  - a senha do SSH só se desliga se existir no authorized_keys do root pelo
 *    menos uma chave que NÃO seja a do painel. Se a única chave for a nossa,
 *    desligar a senha deixava o dono da máquina de fora — salta-se e diz-se
 *    porquê. (É o caso da carina.)
 *  - a firewall só se activa se nada estiver à escuta para fora além do SSH,
 *    do 80 e do 443. Havendo mais alguma coisa, listam-se as portas em vez de
 *    as cortar às cegas.
 *
 * Fica de fora, de propósito, a instalação de actualizações pendentes: reinicia
 * serviços e é a única da lista que pode deitar sites abaixo. Entra com
 * --incluir=updates_pendentes, quando houver uma janela para isso.
 */
class BlindarServidores extends Command
{
    protected $signature = 'seguranca:blindar
                            {servidor? : Id ou nome; sem isto, todos os activos}
                            {--simular : Mostrar o que faria, sem mexer em nada}
                            {--incluir= : Correcções extra (ex: updates_pendentes)}
                            {--excepto= : Saltar estes servidores}
                            {--sem-auditoria-final : Não voltar a auditar no fim}
                            {--forcar : Não perguntar antes de começar}';

    protected $description = 'Audita, corrige e blinda os servidores — com as travas de segurança automáticas';

    /** Correcções que nunca cortam acessos. */
    private const SEGURAS = [
        'wp_config',
        'fail2ban',
        'apache_listagem',
        'apache_assinatura',
        'apache_default',
        'updates_automaticos',
        'escrita_todos',
        'mysql_exposto',
    ];

    /** Correcções que só entram se o servidor passar na respectiva trava. */
    private const CONDICIONADAS = ['ssh_root', 'ssh_senha', 'firewall'];

    public function handle(AuditoriaEndurecimento $auditoria, SshService $ssh): int
    {
        $servidores = $this->servidores();

        if ($servidores->isEmpty()) {
            $this->error('Não encontrei servidores para tratar.');

            return self::FAILURE;
        }

        $extra = collect(explode(',', (string) $this->option('incluir')))
            ->map(fn ($c) => trim($c))->filter()->all();

        $this->newLine();
        $this->line('Máquinas: <info>'.$servidores->pluck('name')->implode(', ').'</info>');
        $this->line('Seguras: <info>'.implode(', ', self::SEGURAS).'</info>');
        $this->line('Condicionadas: <info>'.implode(', ', self::CONDICIONADAS).'</info> (cada uma só entra se a máquina passar na trava)');

        if ($extra) {
            $this->warn('Extra, a teu pedido: '.implode(', ', $extra));
        }

        $this->newLine();

        if ($this->option('simular')) {
            $this->comment('Simulação: nada será executado.');
        } elseif (! $this->option('forcar') && ! $this->confirm('Avançar?', true)) {
            return self::SUCCESS;
        }

        $total = ['feitas' => 0, 'ja_bem' => 0, 'saltadas' => 0, 'falhadas' => 0];

        foreach ($servidores as $servidor) {
            $this->newLine();
            $this->info("── {$servidor->name} ({$servidor->host})");

            try {
                $travas = $this->travas($ssh, $servidor);
            } catch (\Throwable $e) {
                $this->line('   <fg=red>sem ligação</> — '.$e->getMessage());
                $total['falhadas']++;

                continue;
            }

            $chaves = array_merge(self::SEGURAS, $extra);

            foreach (self::CONDICIONADAS as $chave) {
                if ($travas[$chave]['permitido']) {
                    $chaves[] = $chave;
                } else {
                    $this->line("   <fg=yellow>salta</> {$chave} — {$travas[$chave]['porque']}");
                    $total['saltadas']++;
                }
            }

            $catalogo = CatalogoEndurecimento::para($servidor);

            foreach ($chaves as $chave) {
                $verificacao = $catalogo[$chave] ?? null;

                if (! $verificacao || ! $verificacao->correcao) {
                    continue;
                }

                try {
                    $antes = $auditoria->reverificar($servidor, $verificacao);
                } catch (\Throwable $e) {
                    $this->line("   <fg=red>erro</>  {$verificacao->label}: ".$e->getMessage());
                    $total['falhadas']++;

                    continue;
                }

                if (in_array($antes['estado'], ['ok', 'info'], true)) {
                    $total['ja_bem']++;

                    continue;
                }

                if ($this->option('simular')) {
                    $this->line("   <fg=yellow>faria</> {$verificacao->label} — {$antes['detalhe']}");
                    $total['saltadas']++;

                    continue;
                }

                try {
                    $r = $auditoria->corrigir($servidor, $chave);
                } catch (\Throwable $e) {
                    $this->line("   <fg=red>ERRO</>  {$verificacao->label}: ".$e->getMessage());
                    $total['falhadas']++;

                    continue;
                }

                if ($r['resultado']['estado'] === 'ok') {
                    $this->line("   <fg=green>feito</> {$verificacao->label}");
                    $total['feitas']++;
                } else {
                    $this->line("   <fg=yellow>meio</>  {$verificacao->label} — continua: {$r['resultado']['detalhe']}");
                    $total['falhadas']++;
                }
            }

            if (! $this->option('simular') && ! $this->option('sem-auditoria-final')) {
                HardeningAuditResource::auditar($servidor, 'blindagem');
            }
        }

        $this->newLine();
        $this->line("Feitas: {$total['feitas']} · já estavam bem: {$total['ja_bem']} · saltadas: {$total['saltadas']} · por resolver: {$total['falhadas']}");

        if (! $this->option('simular')) {
            $this->comment('O painel, em Infraestrutura → Endurecimento, já tem o retrato actualizado.');
        }

        return self::SUCCESS;
    }

    /**
     * Pergunta à máquina se as correcções de risco são seguras nela.
     *
     * Tudo numa ligação só: são dez servidores e não vale a pena abrir SSH
     * três vezes a cada um para responder a três perguntas.
     *
     * @return array<string, array{permitido: bool, porque: string}>
     */
    private function travas(SshService $ssh, Server $servidor): array
    {
        $porta = (int) ($servidor->port ?: 22);

        $comando = implode("\n", [
            // Quantas chaves há no authorized_keys do root que não sejam a nossa.
            'echo "@@@chaves"',
            "grep -v painel-ateneya /root/.ssh/authorized_keys 2>/dev/null | grep -cE '^(ssh-|ecdsa-|sk-)' || echo 0",
            // Portas à escuta para fora, tirando as três que a firewall abre.
            'echo "@@@portas"',
            'ss -lnt 2>/dev/null | awk \'NR>1{print $4}\' | grep -vE \'^(127\\.|\\[::1\\]|\\[::ffff:127)\' '
                ."| sed 's/.*://' | sort -un | grep -vE '^({$porta}|80|443)$' | tr '\\n' ' '",
        ]);

        $saida = (string) $ssh->run($servidor, $comando, timeout: 60)['output'];

        $blocos = [];
        $actual = null;

        foreach (preg_split('/\R/', $saida) ?: [] as $linha) {
            if (str_starts_with(trim($linha), '@@@')) {
                $actual = trim(substr(trim($linha), 3));
                $blocos[$actual] = '';

                continue;
            }

            if ($actual !== null) {
                $blocos[$actual] = trim($blocos[$actual]."\n".$linha);
            }
        }

        $chavesAlheias = (int) trim($blocos['chaves'] ?? '0');
        $portasExtra   = trim($blocos['portas'] ?? '');

        $temChaveDele = $chavesAlheias > 0;

        $trancaSsh = [
            'permitido' => $temChaveDele,
            'porque'    => $temChaveDele
                ? ''
                : 'a única chave no authorized_keys do root é a do painel — desligar a senha deixava-te sem entrada nesta máquina',
        ];

        return [
            'ssh_root'  => $trancaSsh,
            'ssh_senha' => $trancaSsh,
            'firewall'  => [
                'permitido' => $portasExtra === '',
                'porque'    => $portasExtra === ''
                    ? ''
                    : "há portas à escuta para fora além de {$porta}/80/443 ({$portasExtra}) — abre-as à mão antes de activar a firewall",
            ],
        ];
    }

    private function servidores()
    {
        $excepto = collect(explode(',', (string) $this->option('excepto')))
            ->map(fn ($n) => trim($n))->filter()->all();

        $query = $this->argument('servidor')
            ? Server::where('id', $this->argument('servidor'))->orWhere('name', $this->argument('servidor'))
            : Server::where('is_active', true);

        return $query->orderBy('name')->get()
            ->reject(fn (Server $s) => in_array($s->name, $excepto, true))
            ->values();
    }
}
