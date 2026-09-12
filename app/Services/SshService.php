<?php

namespace App\Services;

use App\Models\Server;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

class SshService
{
    public const PRESET_COMMANDS = [
        'disk'    => ['label' => 'Espaço em disco', 'command' => 'df -h --output=source,size,used,avail,pcent,target | grep -v tmpfs | grep -v udev'],
        'memory'  => ['label' => 'Memória RAM', 'command' => 'free -h'],
        'uptime'  => ['label' => 'Uptime / carga', 'command' => 'uptime && echo && cat /proc/loadavg'],
        'updates' => ['label' => 'Updates disponíveis', 'command' => 'apt-get update -qq 2>/dev/null; apt list --upgradable 2>/dev/null | tail -n +2'],
        'os'      => ['label' => 'SO / versão', 'command' => 'uname -a && cat /etc/os-release 2>/dev/null | head -5'],
        'docker'  => ['label' => 'Containers Docker', 'command' => 'docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" 2>/dev/null || echo "Docker não instalado"'],
        'php'     => ['label' => 'Versões PHP', 'command' => 'php --version 2>/dev/null; ls /etc/php/ 2>/dev/null && echo "---" && plesk ext php --list 2>/dev/null | head -20 || true'],
        'services'=> ['label' => 'Serviços ativos', 'command' => 'systemctl list-units --type=service --state=running --no-pager --no-legend | head -20'],
    ];

    /**
     * @throws \RuntimeException on connection or auth failure
     */
    public function run(Server $server, string $command, int $timeout = 30): array
    {
        $porta   = (int) ($server->port ?: 22);
        $keyPath = self::chaveDoServidor($server);

        try {
            $ssh = new SSH2($server->host, $porta);
            $ssh->setTimeout($timeout);
        } catch (\Throwable $e) {
            // A mensagem crua do phpseclib ("Error 111") não diz a ninguém o
            // que fazer a seguir. Estas são as três causas de sempre.
            throw new \RuntimeException(
                "Não consegui ligar a {$server->name} ({$server->host}:{$porta}). "
                .'Ou o SSH está noutra porta — corrige o campo Porta na ficha do servidor —, '
                .'ou a firewall não deixa entrar o IP deste painel, ou a máquina está em baixo. '
                ."Detalhe: {$e->getMessage()}"
            );
        }

        $key = PublicKeyLoader::load(file_get_contents($keyPath));

        if (! $ssh->login($server->user ?? 'root', $key)) {
            throw new \RuntimeException(
                "A chave foi recusada por {$server->host} (utilizador '".($server->user ?: 'root')."'). "
                .'Confirma que a chave pública do painel está no authorized_keys desse utilizador.'
            );
        }

        $output = $ssh->exec($command);
        $exitCode = $ssh->getExitStatus();

        $ssh->disconnect();

        return [
            'output'    => $output,
            'exit_code' => $exitCode,
        ];
    }

    public function runPreset(Server $server, string $preset): array
    {
        if (! isset(self::PRESET_COMMANDS[$preset])) {
            throw new \InvalidArgumentException("Preset desconhecido: {$preset}");
        }

        return $this->run($server, self::PRESET_COMMANDS[$preset]['command']);
    }

    /**
     * A chave a usar com este servidor: a da ficha, ou a do .env quando a
     * ficha não tem nenhuma (SSH_CHAVE_POR_OMISSAO).
     *
     * Vale a pena dizer aqui porque é que isto falha tantas vezes: o painel
     * corre como o utilizador do vhost, não como root. Uma chave em
     * /root/.ssh/ existe mas não se abre, e o erro parece o mesmo de não
     * existir. Por isso a mensagem distingue os dois casos.
     */
    public static function chaveDoServidor(Server $server): string
    {
        $caminho = self::expandTilde($server->ssh_key_path ?: config('ssh.chave_por_omissao'));

        if (! $caminho) {
            throw new \RuntimeException(
                "O servidor {$server->name} não tem chave SSH configurada. "
                .'Preenche "Caminho da chave SSH" na ficha do servidor, ou define SSH_CHAVE_POR_OMISSAO no .env do painel.'
            );
        }

        if (! file_exists($caminho)) {
            throw new \RuntimeException(
                "Não encontrei a chave SSH em {$caminho} (caminho no servidor do painel, não no teu computador)."
            );
        }

        if (! is_readable($caminho)) {
            throw new \RuntimeException(
                "A chave {$caminho} existe mas o painel não a consegue ler. "
                .'Corre como o utilizador do vhost — uma chave em /root/.ssh/ não serve. '
                .'Põe a chave numa pasta do painel e dá-lhe dono do vhost com permissões 600.'
            );
        }

        return $caminho;
    }

    public static function expandTilde(?string $path): ?string
    {
        if (! $path) {
            return $path;
        }

        if ($path === '~' || str_starts_with($path, '~/') || str_starts_with($path, '~\\')) {
            $home = PHP_OS_FAMILY === 'Windows'
                ? (getenv('USERPROFILE') ?: getenv('HOMEDRIVE') . getenv('HOMEPATH'))
                : (getenv('HOME') ?: (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['dir'] : null));

            if ($home) {
                $path = $home . substr($path, 1);
            }
        }

        return $path;
    }
}
