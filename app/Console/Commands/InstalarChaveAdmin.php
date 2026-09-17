<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\SshService;
use Illuminate\Console\Command;

/**
 * Poe a chave PUBLICA de uma pessoa no authorized_keys de todos os servidores,
 * usando o acesso que o painel ja tem.
 *
 * Porque existe: o `seguranca:blindar` de 12/09/2026 desligou a entrada do root
 * por senha. Num computador novo — ou num onde a chave nunca foi instalada —
 * deixa de haver maneira de entrar por SSH. O painel continua a entrar em todos
 * com a chave dele, e e por ai que se instala a tua.
 *
 *   php artisan seguranca:instalar-chave "ssh-ed25519 AAAA... andre@PC"
 *   php artisan seguranca:instalar-chave "ssh-ed25519 AAAA... andre@PC" contabo-a
 *   php artisan seguranca:instalar-chave "..." --simular
 *
 * Aceita so chaves publicas (uma linha, tipo conhecido). Uma chave privada
 * colada por engano e recusada antes de sair daqui. Correr duas vezes nao
 * duplica a linha.
 */
class InstalarChaveAdmin extends Command
{
    protected $signature = 'seguranca:instalar-chave
                            {chave : A chave PUBLICA, entre aspas (conteudo do ficheiro .pub)}
                            {servidor? : Id ou nome; sem isto, todos os activos}
                            {--simular : So mostrar onde instalaria}';

    protected $description = 'Instala uma chave SSH publica de administracao em todos os servidores, pelo acesso do painel';

    private const TIPOS = 'ssh-ed25519|ssh-rsa|ecdsa-sha2-nistp256|ecdsa-sha2-nistp384|ecdsa-sha2-nistp521|sk-ssh-ed25519@openssh\.com|sk-ecdsa-sha2-nistp256@openssh\.com';

    public function handle(SshService $ssh): int
    {
        $chave = trim((string) $this->argument('chave'));

        if (str_contains($chave, 'PRIVATE KEY')) {
            $this->error('Isto e uma chave PRIVADA. Nunca a copies para lado nenhum — usa o ficheiro que acaba em .pub.');

            return self::FAILURE;
        }

        if (! preg_match('/^(' . self::TIPOS . ') ([A-Za-z0-9+\/]+={0,3})( [^\r\n\'"\\\\]*)?$/', $chave, $m)) {
            $this->error('Nao parece uma chave publica SSH (esperava "ssh-ed25519 AAAA... comentario", numa linha).');

            return self::FAILURE;
        }

        $corpo = $m[2];

        // Sem comentario, a linha fica anonima no authorized_keys e ninguem sabe
        // de quem e daqui a seis meses. E o `seguranca:blindar` so conta chaves
        // que nao sejam do painel nem do agente — um comentario ajuda a ler isso.
        if (trim($m[3] ?? '') === '') {
            $chave .= ' admin-' . now()->format('Ymd');
        }

        $servidores = $this->argument('servidor')
            ? Server::where('id', $this->argument('servidor'))->orWhere('name', $this->argument('servidor'))->get()
            : Server::where('is_active', true)->orderBy('name')->get();

        if ($servidores->isEmpty()) {
            $this->error('Nao encontrei servidores.');

            return self::FAILURE;
        }

        // Grava no authorized_keys do utilizador com que o painel entra (root na
        // maioria). grep -F pelo corpo da chave: o comentario pode mudar, a chave nao.
        $remoto = implode(' && ', [
            'mkdir -p ~/.ssh',
            'chmod 700 ~/.ssh',
            'touch ~/.ssh/authorized_keys',
            'chmod 600 ~/.ssh/authorized_keys',
            '(grep -qF ' . escapeshellarg($corpo) . ' ~/.ssh/authorized_keys && echo JA-EXISTIA'
                . ' || (printf "%s\n" ' . escapeshellarg($chave) . ' >> ~/.ssh/authorized_keys && echo INSTALADA))',
        ]);

        $ok = 0;
        $falhas = [];

        foreach ($servidores as $servidor) {
            $alvo = str_pad($servidor->name, 24) . ' ' . ($servidor->user ?: 'root') . '@' . $servidor->host;

            if ($this->option('simular')) {
                $this->line("  faria   {$alvo}");

                continue;
            }

            try {
                $r = $ssh->run($servidor, $remoto, timeout: 30);
                $saida = trim((string) $r['output']);

                if (str_contains($saida, 'INSTALADA') || str_contains($saida, 'JA-EXISTIA')) {
                    $this->line('  <fg=green>' . (str_contains($saida, 'JA-EXISTIA') ? 'ja tinha' : 'feito  ') . "</> {$alvo}");
                    $ok++;
                } else {
                    $this->line("  <fg=red>falhou </> {$alvo} — {$saida}");
                    $falhas[] = $servidor->name;
                }
            } catch (\Throwable $e) {
                $this->line("  <fg=red>falhou </> {$alvo} — " . $e->getMessage());
                $falhas[] = $servidor->name;
            }
        }

        $this->newLine();

        if ($this->option('simular')) {
            $this->comment('Simulacao: nada foi instalado.');

            return self::SUCCESS;
        }

        $this->info("Instalada/confirmada em {$ok} servidor(es).");

        if ($falhas) {
            $this->warn('Por fazer: ' . implode(', ', $falhas) . ' — nesses entra pela consola VNC do fornecedor ou pelo SSH Terminal do Plesk.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
