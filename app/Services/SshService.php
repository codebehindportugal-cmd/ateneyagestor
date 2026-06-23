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
        $ssh = new SSH2($server->host, $server->port ?? 22);
        $ssh->setTimeout($timeout);

        $keyPath = $server->ssh_key_path;
        if (! $keyPath || ! file_exists($keyPath)) {
            throw new \RuntimeException("Chave SSH não encontrada: {$keyPath}. Configura o caminho da chave na ficha do servidor.");
        }

        $key = PublicKeyLoader::load(file_get_contents($keyPath));

        if (! $ssh->login($server->user ?? 'root', $key)) {
            throw new \RuntimeException("Autenticação SSH falhou em {$server->host}. Verifica o utilizador e a chave.");
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
}
