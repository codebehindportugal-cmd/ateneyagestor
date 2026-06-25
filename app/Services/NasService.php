<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class NasService
{
    private string $host;
    private string $user;
    private string $keyPath;
    private string $basePath;
    private int $port;
    private string $proxyCmd;

    public function __construct()
    {
        $cfg = config('backup.nas');
        $this->host     = $cfg['host'];
        $this->user     = $cfg['user'];
        $this->keyPath  = $cfg['key_path'];
        $this->basePath = rtrim($cfg['base_path'], '/');
        $this->port     = $cfg['port'];
        $this->proxyCmd = $cfg['proxy_cmd'];
    }

    public function isConfigured(): bool
    {
        return filled($this->host) && filled($this->user) && filled($this->keyPath);
    }

    /**
     * Upload a local file to NAS under basePath/subDir/.
     * $remoteFilename overrides the basename of $localFile on the remote side.
     * Returns the full remote path of the uploaded file.
     */
    public function upload(string $localFile, string $subDir, ?string $remoteFilename = null): string
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException(
                'NAS não configurado. Define NAS_HOST, NAS_USER e NAS_KEY_PATH no .env do servidor.'
            );
        }

        $remoteDir  = $this->basePath . '/' . ltrim($subDir, '/');
        $remoteFile = $remoteDir . '/' . ($remoteFilename ?? basename($localFile));

        // Ensure remote directory exists
        $this->ssh("mkdir -p " . escapeshellarg($remoteDir));

        // Upload file
        $this->scp($localFile, "{$this->user}@{$this->host}:{$remoteFile}");

        return $remoteFile;
    }

    /**
     * Download a file from NAS to a local temp file. Returns the local temp path.
     * Caller is responsible for deleting the temp file after use.
     */
    public function downloadToTemp(string $remotePath): string
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('NAS não configurado.');
        }

        $tmpFile = sys_get_temp_dir() . '/nasdl-' . uniqid() . '_' . basename($remotePath);
        $this->scpFrom("{$this->user}@{$this->host}:{$remotePath}", $tmpFile);

        return $tmpFile;
    }

    /**
     * Delete a file on the NAS.
     */
    public function deleteFile(string $remotePath): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $this->ssh("rm -f " . escapeshellarg($remotePath));
    }

    /**
     * List backup files on NAS under basePath. Returns array of remote paths.
     */
    public function listFiles(?string $subDir = null): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $path   = $this->basePath . ($subDir ? '/' . ltrim($subDir, '/') : '');
        $output = $this->ssh("find " . escapeshellarg($path) . " -type f 2>/dev/null | sort");

        return array_values(array_filter(explode("\n", trim($output))));
    }

    /**
     * Get directory tree as string for display.
     */
    public function tree(?string $subDir = null): string
    {
        if (! $this->isConfigured()) {
            return '(NAS não configurado)';
        }

        $path = $this->basePath . ($subDir ? '/' . ltrim($subDir, '/') : '');
        $out  = $this->ssh("find " . escapeshellarg($path) . " 2>/dev/null | sed 's|" . $this->basePath . "/||' | sort");

        return trim($out) ?: '(vazio)';
    }

    /**
     * Total size under basePath in bytes.
     */
    public function totalSize(?string $subDir = null): int
    {
        if (! $this->isConfigured()) {
            return 0;
        }

        $path = $this->basePath . ($subDir ? '/' . ltrim($subDir, '/') : '');
        $out  = $this->ssh("du -sb " . escapeshellarg($path) . " 2>/dev/null | awk '{print $1}'");

        return (int) trim($out);
    }

    // --- private SSH/SCP helpers ---

    private function ssh(string $command): string
    {
        $cmd = $this->buildSshCmd($command);

        $process = new Process($cmd, timeout: 60);
        $process->run();

        if (! $process->isSuccessful() && $process->getExitCode() !== 1) {
            throw new \RuntimeException(
                "NAS SSH falhou: " . ($process->getErrorOutput() ?: $process->getOutput())
            );
        }

        return $process->getOutput();
    }

    private function scpFrom(string $remote, string $localFile): void
    {
        $cmd = ['scp', '-P', (string) $this->port, '-i', $this->keyPath,
            '-o', 'StrictHostKeyChecking=no',
            '-o', 'BatchMode=yes',
            '-o', 'ConnectTimeout=30',
        ];

        if ($this->proxyCmd) {
            $cmd[] = '-o';
            $cmd[] = "ProxyCommand={$this->proxyCmd}";
        }

        $cmd[] = $remote;
        $cmd[] = $localFile;

        $process = new Process($cmd, timeout: 120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(
                "SCP download do NAS falhou: " . $process->getErrorOutput()
            );
        }
    }

    private function scp(string $localFile, string $remote): void
    {
        $cmd = ['scp', '-P', (string) $this->port, '-i', $this->keyPath,
            '-o', 'StrictHostKeyChecking=no',
            '-o', 'BatchMode=yes',
            '-o', 'ConnectTimeout=30',
        ];

        if ($this->proxyCmd) {
            $cmd[] = '-o';
            $cmd[] = "ProxyCommand={$this->proxyCmd}";
        }

        $cmd[] = $localFile;
        $cmd[] = $remote;

        $process = new Process($cmd, timeout: 600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(
                "SCP para NAS falhou ao enviar " . basename($localFile) . ": " . $process->getErrorOutput()
            );
        }
    }

    private function buildSshCmd(string $command): array
    {
        $cmd = ['ssh', '-p', (string) $this->port, '-i', $this->keyPath,
            '-o', 'StrictHostKeyChecking=no',
            '-o', 'BatchMode=yes',
            '-o', 'ConnectTimeout=30',
        ];

        if ($this->proxyCmd) {
            $cmd[] = '-o';
            $cmd[] = "ProxyCommand={$this->proxyCmd}";
        }

        $cmd[] = "{$this->user}@{$this->host}";
        $cmd[] = $command;

        return $cmd;
    }
}
