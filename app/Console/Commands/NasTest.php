<?php

namespace App\Console\Commands;

use App\Services\NasService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Diagnóstico da ligação SSH ao NAS.
 *
 * Existe porque o NasService só devolve o stderr cru do ssh
 * ("Permission denied (publickey,password)"), que não distingue entre
 * chave ilegível pelo utilizador do processo, chave recusada pelo NAS,
 * config em cache desatualizada ou problema de rede.
 *
 * Corre como o MESMO utilizador que falha:
 *   sudo -u www-data php artisan nas:test        (botão "Backup agora" → PHP-FPM)
 *   php artisan nas:test                          (cron/scheduler)
 */
class NasTest extends Command
{
    protected $signature = 'nas:test
        {--write        : Testa também escrita real (mkdir + upload + apagar um ficheiro temporário)}
        {--full-verbose : Mostra o output completo do ssh -vvv em vez das linhas relevantes}';

    protected $description = 'Diagnostica a ligação SSH ao NAS (utilizador, chave, rede, autenticação)';

    private array $problems = [];
    private array $hints    = [];

    public function handle(NasService $nas): int
    {
        $this->line('');
        $this->line('<fg=cyan>═══ nas:test — diagnóstico da ligação ao NAS ═══</>');

        $cfg = config('backup.nas');

        $this->sectionContext();
        $this->sectionConfig($cfg);
        $keyPath = $this->sectionKey($cfg['key_path'] ?? '');
        $this->sectionNetwork($cfg);
        $this->sectionAuth($cfg, $keyPath);

        if ($this->option('write')) {
            $this->sectionWrite($nas);
        }

        return $this->sectionVerdict();
    }

    // ---------------------------------------------------------------- contexto

    private function sectionContext(): void
    {
        $this->header('1. Contexto do processo');

        $user = function_exists('posix_geteuid') && function_exists('posix_getpwuid')
            ? (posix_getpwuid(posix_geteuid())['name'] ?? '?')
            : (getenv('USERNAME') ?: getenv('USER') ?: '?');

        $home = getenv('HOME') ?: (getenv('USERPROFILE') ?: '(não definido)');

        $this->kv('Utilizador do processo', $user);
        $this->kv('HOME', $home);
        $this->kv('SAPI / binário PHP', PHP_SAPI . '  ' . PHP_BINARY);
        $this->kv('Caminho da app', base_path());
        $this->kv('ssh disponível', $this->which('ssh') ?: '<fg=red>NÃO ENCONTRADO no PATH</>');

        if (! $this->which('ssh')) {
            $this->problems[] = 'O binário "ssh" não está no PATH deste processo.';
            $this->hints[]    = 'Instala openssh-client ou garante que /usr/bin está no PATH do PHP-FPM.';
        }

        $this->note(
            'O botão "Backup agora" lança o artisan a partir do PHP-FPM, logo corre como o utilizador '
            . 'do web server (tipicamente www-data). O cron das 03:00 corre como o dono do crontab '
            . '(tipicamente root). Corre este comando com "sudo -u www-data" para reproduzir o erro do botão.'
        );
    }

    // ------------------------------------------------------------------ config

    private function sectionConfig(array $cfg): void
    {
        $this->header('2. Configuração (config/backup.php ← .env)');

        $this->kv('NAS_HOST', $cfg['host'] !== '' ? $cfg['host'] : '<fg=red>(vazio)</>');
        $this->kv('NAS_USER', (string) $cfg['user']);
        $this->kv('NAS_PORT', (string) $cfg['port']);
        $this->kv('NAS_BASE_PATH', (string) $cfg['base_path']);
        $this->kv('NAS_KEY_PATH', $cfg['key_path'] !== '' ? $cfg['key_path'] : '<fg=red>(vazio)</>');
        $this->kv('NAS_SSH_PROXY_CMD', $cfg['proxy_cmd'] !== '' ? $cfg['proxy_cmd'] : '(nenhum)');

        foreach (['host' => 'NAS_HOST', 'user' => 'NAS_USER', 'key_path' => 'NAS_KEY_PATH'] as $key => $envVar) {
            if (blank($cfg[$key])) {
                $this->problems[] = "{$envVar} está vazio — o NasService considera o NAS 'não configurado'.";
            }
        }

        // Config em cache mais antiga que o .env é uma causa silenciosa clássica:
        // o deploy corre config:cache, alterações posteriores ao .env não têm efeito.
        $cache   = base_path('bootstrap/cache/config.php');
        $envFile = base_path('.env');

        if (is_file($cache)) {
            $this->kv('Config em cache', $cache . '  (' . date('d/m/Y H:i', filemtime($cache)) . ')');

            if (is_file($envFile) && filemtime($envFile) > filemtime($cache)) {
                $this->problems[] = 'O .env foi alterado DEPOIS do config:cache — os valores acima podem estar desatualizados.';
                $this->hints[]    = 'Corre: php artisan config:clear && php artisan config:cache';
            }
        } else {
            $this->kv('Config em cache', '(não — a ler do .env directamente)');
        }
    }

    // -------------------------------------------------------------------- chave

    private function sectionKey(string $rawPath): string
    {
        $this->header('3. Chave privada SSH');

        if (blank($rawPath)) {
            $this->line('  <fg=red>Sem NAS_KEY_PATH definido.</>');

            return '';
        }

        // O NasService usa o valor cru, sem expandir "~". Se o .env tiver um til,
        // o caminho literal não existe e o ssh cai para autenticação por password.
        $path = $rawPath;
        if (str_starts_with($path, '~')) {
            $this->problems[] = 'NAS_KEY_PATH começa por "~" — o NasService NÃO expande o til, o ssh recebe o caminho literal.';
            $this->hints[]    = 'Usa o caminho absoluto no .env (ex.: /root/.ssh/ateneya_nas_key).';
            $path = (getenv('HOME') ?: '') . substr($path, 1);
            $this->kv('Caminho expandido (só para este teste)', $path);
        }

        $this->kv('Caminho', $path);

        if (! file_exists($path)) {
            $this->kv('Existe', '<fg=red>NÃO</>');
            $this->problems[] = "A chave {$path} não existe (ou está fora do alcance deste utilizador).";
            $this->hints[]    = 'Confirma o caminho e que o directório-pai é atravessável (+x) por este utilizador.';

            return $path;
        }

        $this->kv('Existe', '<fg=green>sim</>');

        $readable = is_readable($path);
        $this->kv('Legível por este utilizador', $readable ? '<fg=green>sim</>' : '<fg=red>NÃO</>');

        if (! $readable) {
            $this->problems[] = "Este utilizador não consegue ler {$path}.";
            $this->hints[]    = 'Copia a chave para um sítio legível pelo www-data (ex.: /etc/ssh/ateneya_nas_key, '
                . 'chown root:www-data, chmod 640) e aponta NAS_KEY_PATH para lá.';
        }

        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $this->kv('Permissões', $perms);

        if ($readable && ! in_array($perms, ['0600', '0400', '0640', '0440'], true)) {
            $this->problems[] = "Permissões {$perms} demasiado abertas — o ssh recusa chaves com acesso group/other de escrita.";
            $this->hints[]    = "chmod 600 {$path}";
        }

        if (function_exists('posix_getpwuid')) {
            $owner = posix_getpwuid(fileowner($path))['name'] ?? fileowner($path);
            $group = function_exists('posix_getgrgid')
                ? (posix_getgrgid(filegroup($path))['name'] ?? filegroup($path))
                : filegroup($path);
            $this->kv('Dono', "{$owner}:{$group}");
        }

        if ($readable) {
            $lines = @file($path) ?: [];
            $first = trim((string) ($lines[0] ?? ''));
            $this->kv('Formato', $first !== '' ? $first : '(vazio)');

            if (! str_contains($first, 'PRIVATE KEY')) {
                $this->problems[] = 'O ficheiro não parece uma chave PRIVADA (a primeira linha não tem "PRIVATE KEY").';
                $this->hints[]    = 'Talvez estejas a apontar para a chave pública (.pub) por engano.';
            }

            // A pública correspondente ajuda a comparar com o authorized_keys do NAS.
            $pub = $this->which('ssh-keygen')
                ? $this->run(['ssh-keygen', '-y', '-f', $path], 15)
                : null;

            if ($pub && $pub['ok']) {
                $this->line('');
                $this->line('  <fg=yellow>Chave pública correspondente</> (tem de estar no authorized_keys do NAS):');
                $this->line('  ' . trim($pub['out']));
            } elseif ($pub) {
                $this->problems[] = 'O ssh-keygen não conseguiu ler a chave privada: ' . trim($pub['err']);
                $this->hints[]    = 'Chave protegida por passphrase? Com BatchMode=yes o ssh não a consegue usar.';
            }
        }

        return $path;
    }

    // -------------------------------------------------------------------- rede

    private function sectionNetwork(array $cfg): void
    {
        $this->header('4. Rede');

        if (filled($cfg['proxy_cmd'])) {
            $this->note('Há ProxyCommand definido — o teste de porta directo abaixo pode falhar sem que isso seja um problema.');
        }

        if (blank($cfg['host'])) {
            return;
        }

        $started = microtime(true);
        $sock    = @fsockopen($cfg['host'], (int) $cfg['port'], $errno, $errstr, 10);
        $ms      = (int) round((microtime(true) - $started) * 1000);

        if ($sock) {
            $banner = trim((string) @fgets($sock, 256));
            fclose($sock);
            $this->kv("TCP {$cfg['host']}:{$cfg['port']}", "<fg=green>aberto</> ({$ms} ms)");
            $this->kv('Banner SSH', $banner !== '' ? $banner : '(sem banner)');
        } else {
            $this->kv("TCP {$cfg['host']}:{$cfg['port']}", "<fg=red>fechado</> — {$errstr} ({$errno})");
            $this->problems[] = "Não há ligação TCP a {$cfg['host']}:{$cfg['port']}.";
            $this->hints[]    = 'Verifica se o NAS está ligado, a rota/VPN para a rede 10.0.0.0/24 e a firewall.';
        }
    }

    // ------------------------------------------------------------ autenticação

    private function sectionAuth(array $cfg, string $keyPath): void
    {
        $this->header('5. Autenticação (ssh -vvv)');

        if (blank($cfg['host']) || blank($keyPath)) {
            $this->line('  (saltado — falta host ou chave)');

            return;
        }

        $cmd = ['ssh', '-vvv', '-p', (string) $cfg['port'], '-i', $keyPath,
            '-o', 'StrictHostKeyChecking=no',
            '-o', 'BatchMode=yes',
            '-o', 'ConnectTimeout=30',
            // IdentitiesOnly evita que o ssh ofereça outras identidades antes desta
            // e leve a "Too many authentication failures" antes de chegar à certa.
            '-o', 'IdentitiesOnly=yes',
        ];

        if (filled($cfg['proxy_cmd'])) {
            $cmd[] = '-o';
            $cmd[] = "ProxyCommand={$cfg['proxy_cmd']}";
        }

        $cmd[] = "{$cfg['user']}@{$cfg['host']}";
        $cmd[] = 'echo NAS_OK; id; uname -a';

        $result = $this->run($cmd, 60);
        $all    = $result['out'] . "\n" . $result['err'];

        if ($this->option('full-verbose')) {
            $this->line($all);
        } else {
            $keep = [
                'Authenticating', 'Offering public key', 'Server accepts key',
                'Authentications that can continue', 'send_pubkey_test',
                'we sent a publickey packet', 'Trying private key', 'Load key',
                'Permission denied', 'no such identity', 'Connection closed',
                'Connection refused', 'Connection timed out', 'banner',
                'debug1: Authentication succeeded', 'REMOTE HOST IDENTIFICATION',
                'NAS_OK', 'uid=',
            ];

            foreach (explode("\n", $all) as $line) {
                foreach ($keep as $needle) {
                    if (stripos($line, $needle) !== false) {
                        $this->line('  ' . trim($line));
                        break;
                    }
                }
            }
        }

        $this->line('');
        $this->kv('Código de saída', (string) $result['code']);

        if (str_contains($all, 'NAS_OK')) {
            $this->line('  <fg=green>✔ Autenticação bem sucedida — o SSH ao NAS funciona para este utilizador.</>');

            return;
        }

        $this->problems[] = 'A autenticação SSH ao NAS falhou para este utilizador.';
        $this->interpret($all, $cfg);
    }

    /**
     * Traduz os padrões do ssh -vvv em causa provável, para não ficar
     * só o "Permission denied (publickey,password)" que não diz nada.
     */
    private function interpret(string $out, array $cfg): void
    {
        if (stripos($out, 'not accessible') !== false || stripos($out, 'no such identity') !== false) {
            $this->hints[] = 'O ssh nem chegou a carregar a chave (ficheiro inacessível). Ver secção 3.';

            return;
        }

        if (stripos($out, 'Load key') !== false && stripos($out, 'invalid format') !== false) {
            $this->hints[] = 'Chave em formato inválido — reexporta em OpenSSH (ssh-keygen -p -f chave -m PEM se o NAS for antigo).';

            return;
        }

        if (stripos($out, 'REMOTE HOST IDENTIFICATION') !== false) {
            $this->hints[] = "A host key de {$cfg['host']} mudou — o IP pode estar agora noutra máquina, "
                . 'ou o NAS foi reinstalado. Limpa a entrada: ssh-keygen -R ' . $cfg['host'];

            return;
        }

        if (stripos($out, 'Server accepts key') !== false) {
            $this->hints[] = 'O NAS aceitou a chave mas a sessão não abriu — normalmente shell inválida '
                . 'para o utilizador no NAS, ou o volume/home indisponível.';

            return;
        }

        if (stripos($out, 'Offering public key') !== false || stripos($out, 'send_pubkey_test') !== false) {
            $this->hints[] = "A chave foi oferecida e o NAS RECUSOU-A. Verifica no NAS, como {$cfg['user']}:";
            $this->hints[] = '  • a pública (secção 3) está em ~/.ssh/authorized_keys';
            $this->hints[] = '  • permissões: chmod 755 ~ ; chmod 700 ~/.ssh ; chmod 600 ~/.ssh/authorized_keys';
            $this->hints[] = '  • /etc/ssh/sshd_config: PubkeyAuthentication yes e PermitRootLogin prohibit-password (ou yes)';
            $this->hints[] = '  • uma actualização de DSM/firmware repõe estes três pontos — é a causa mais comum';
            $this->hints[] = '  • confirma no NAS: tail -50 /var/log/auth.log  (ou /var/log/messages)';

            return;
        }

        $this->hints[] = 'Corre com --full-verbose para ver o output completo do ssh -vvv.';
    }

    // ------------------------------------------------------------------ escrita

    private function sectionWrite(NasService $nas): void
    {
        $this->header('6. Teste de escrita real (via NasService)');

        if (! $nas->isConfigured()) {
            $this->line('  <fg=red>NasService diz que o NAS não está configurado.</>');

            return;
        }

        $local = tempnam(sys_get_temp_dir(), 'nastest');
        file_put_contents($local, 'nas:test ' . now()->toDateTimeString() . "\n");

        try {
            $remote = $nas->upload($local, '_diagnostico', 'nas-test-' . now()->format('Ymd-His') . '.txt');
            $this->line("  <fg=green>✔ Upload OK</> → {$remote}");

            $nas->deleteFile($remote);
            $this->line('  <fg=green>✔ Remoção OK</> — escrita e leitura no NAS a funcionar.');
        } catch (\Throwable $e) {
            $this->line('  <fg=red>✘ ' . $e->getMessage() . '</>');
            $this->problems[] = 'O NasService falhou na escrita: ' . $e->getMessage();
        } finally {
            @unlink($local);
        }
    }

    // ---------------------------------------------------------------- veredicto

    private function sectionVerdict(): int
    {
        $this->header('Veredicto');

        if (! $this->problems) {
            $this->line('  <fg=green>Sem problemas detectados.</>');
            $this->line('');

            return self::SUCCESS;
        }

        foreach ($this->problems as $p) {
            $this->line("  <fg=red>✘</> {$p}");
        }

        if ($this->hints) {
            $this->line('');
            $this->line('  <fg=yellow>Sugestões:</>');
            foreach ($this->hints as $h) {
                $this->line("  → {$h}");
            }
        }

        $this->line('');

        return self::FAILURE;
    }

    // ------------------------------------------------------------------ helpers

    private function run(array $cmd, int $timeout): array
    {
        $process = new Process($cmd, timeout: $timeout);
        $process->run();

        return [
            'ok'   => $process->isSuccessful(),
            'code' => (int) $process->getExitCode(),
            'out'  => $process->getOutput(),
            'err'  => $process->getErrorOutput(),
        ];
    }

    private function which(string $bin): ?string
    {
        $finder = new \Symfony\Component\Process\ExecutableFinder();

        return $finder->find($bin);
    }

    private function header(string $title): void
    {
        $this->line('');
        $this->line("<fg=cyan>── {$title} ──</>");
    }

    private function kv(string $key, string $value): void
    {
        $this->line('  ' . str_pad($key, 32, '.') . ' ' . $value);
    }

    private function note(string $text): void
    {
        $this->line('');
        $this->line('  <fg=gray>' . wordwrap($text, 100, "\n  ") . '</>');
    }
}
