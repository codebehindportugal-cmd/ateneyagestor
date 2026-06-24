<?php

namespace App\Services;

use App\Enums\BackupStatus;
use App\Enums\ServerType;
use App\Models\BackupRun;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;

class BackupService
{
    public function __construct(private readonly NasService $nas) {}

    /**
     * Run backup for one server.
     * Returns the BackupRun record (either success or failed).
     */
    public function backup(Server $server, string $triggeredBy = 'command', ?callable $log = null): BackupRun
    {
        $logger = $log ?? fn (string $msg) => Log::info("[backup:{$server->name}] {$msg}");

        $run = BackupRun::create([
            'server_id'    => $server->id,
            'agent_id'     => null,
            'status'       => BackupStatus::Running->value,
            'started_at'   => now(),
            'triggered_by' => $triggeredBy,
        ]);

        $localTmpDir = config('backup.tmp_dir') . '/run-' . $run->id;
        $logLines    = [];

        $captureLog = function (string $msg) use (&$logLines, $logger) {
            $logLines[] = '[' . now()->format('H:i:s') . '] ' . $msg;
            $logger($msg);
        };

        try {
            $captureLog("Iniciando backup de {$server->name} ({$server->type->value})...");

            $localFiles = $this->createBackupOnServer($server, $localTmpDir, $captureLog);

            if (empty($localFiles)) {
                throw new \RuntimeException('Nenhum ficheiro de backup foi criado.');
            }

            $totalSize = array_sum(array_map('filesize', $localFiles));
            $captureLog("Backup criado: " . count($localFiles) . " ficheiro(s), " . $this->humanSize($totalSize));

            $nasPaths = [];
            if ($this->nas->isConfigured()) {
                $nasDir = $this->nasDirectory($server);
                $captureLog("A enviar para NAS: {$nasDir}");
                foreach ($localFiles as $file) {
                    $path       = $this->nas->upload($file, $nasDir);
                    $nasPaths[] = $path;
                    $captureLog("  → " . basename($file) . " enviado.");
                }
            } else {
                $captureLog("NAS não configurado — ficheiros ficam em {$localTmpDir}.");
            }

            $run->update([
                'status'      => BackupStatus::Success->value,
                'finished_at' => now(),
                'nas_path'    => $nasPaths ? $nasPaths[0] : null,
                'file_count'  => count($localFiles),
                'size_bytes'  => $totalSize,
                'log'         => implode("\n", $logLines),
            ]);

            $captureLog("Backup concluído com sucesso.");
        } catch (\Throwable $e) {
            $captureLog("ERRO: " . $e->getMessage());

            $run->update([
                'status'      => BackupStatus::Failed->value,
                'finished_at' => now(),
                'error'       => $e->getMessage(),
                'log'         => implode("\n", $logLines),
            ]);
        } finally {
            $this->cleanupTmp($localTmpDir);
        }

        return $run->fresh();
    }

    // ---------------------------------------------------------------------------
    // Per-type backup strategies
    // ---------------------------------------------------------------------------

    private function createBackupOnServer(Server $server, string $localTmpDir, callable $log): array
    {
        $keyPath = config('backup.ssh_key') ?: $server->ssh_key_path;

        if (! $keyPath || ! file_exists($keyPath)) {
            throw new \RuntimeException(
                "Chave SSH não encontrada: {$keyPath}. Define BACKUP_SSH_KEY no .env do servidor."
            );
        }

        $key  = PublicKeyLoader::load(file_get_contents($keyPath));
        $host = $server->host;
        $port = $server->port ?? 22;
        $user = $server->user ?? 'root';

        $log("Conectando a {$user}@{$host}:{$port}...");

        $sftp = new SFTP($host, $port);
        $sftp->setTimeout(3600);

        if (! $sftp->login($user, $key)) {
            throw new \RuntimeException("Autenticação SSH falhou em {$host}.");
        }

        $log("Ligado.");

        $remoteTmp = '/tmp/bm-' . Str::uuid();
        $sftp->exec("mkdir -p {$remoteTmp}");

        try {
            $remoteFiles = match ($server->type) {
                ServerType::WordPress  => $this->backupWordPress($sftp, $server, $remoteTmp, $log),
                ServerType::VpsLaravel => $this->backupLaravel($sftp, $server, $remoteTmp, $log),
                ServerType::Plesk      => $this->backupPlesk($sftp, $server, $remoteTmp, $log),
                default                => throw new \RuntimeException(
                    "Tipo '{$server->type->value}' ainda não suporta backup directo."
                ),
            };

            @mkdir($localTmpDir, 0755, true);
            $localFiles = [];

            foreach ($remoteFiles as $remoteFile) {
                $local = $localTmpDir . '/' . basename($remoteFile);
                $log("A descarregar " . basename($remoteFile) . "...");

                if (! $sftp->get($remoteFile, $local)) {
                    throw new \RuntimeException("Falhou ao descarregar {$remoteFile}");
                }

                $localFiles[] = $local;
            }

            return $localFiles;
        } finally {
            $sftp->exec("rm -rf {$remoteTmp}");
            $sftp->disconnect();
        }
    }

    private function backupWordPress(SFTP $sftp, Server $server, string $tmpDir, callable $log, ?string $wpRootOverride = null): array
    {
        $wpRoot = $wpRootOverride ? rtrim($wpRootOverride, '/') : rtrim($server->wp_root ?? '', '/');

        if (! $wpRoot) {
            throw new \RuntimeException("wp_root não configurado para {$server->name}. Configura o caminho WordPress na ficha do servidor.");
        }

        $log("WordPress root: {$wpRoot}");

        // Extract DB credentials from wp-config.php
        $cfgContent = $sftp->exec(
            "grep -E \"define\\s*\\(\\s*'DB_(NAME|USER|PASSWORD|HOST)'\" {$wpRoot}/wp-config.php 2>/dev/null"
        );

        preg_match_all(
            "/define\s*\(\s*'DB_(NAME|USER|PASSWORD|HOST)'\s*,\s*'([^']*)'/",
            $cfgContent,
            $m
        );
        $creds = array_combine($m[1], $m[2]);

        $dbName = $creds['NAME'] ?? '';
        $dbUser = $creds['USER'] ?? '';
        $dbPass = $creds['PASSWORD'] ?? '';
        $dbHost = $creds['HOST'] ?? 'localhost';

        if (! $dbName) {
            throw new \RuntimeException("Não foi possível ler as credenciais de BD do wp-config.php em {$wpRoot}");
        }

        $log("BD: {$dbName}@{$dbHost}");

        // DB dump
        $dbFile  = "{$tmpDir}/db.sql.gz";
        $dumpCmd = sprintf(
            "mysqldump -h%s -u%s -p%s %s 2>/dev/null | gzip > %s; echo exit:$?",
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            $dbFile
        );
        $sftp->exec($dumpCmd);
        $log("mysqldump concluído.");

        // Files: wp-content (exclude cache/logs)
        $filesFile = "{$tmpDir}/files.tar.gz";
        $sftp->exec(
            "tar -czf {$filesFile}" .
            " --exclude='{$wpRoot}/wp-content/cache'" .
            " --exclude='{$wpRoot}/wp-content/wflogs'" .
            " --exclude='{$wpRoot}/wp-content/updraft'" .
            " -C {$wpRoot} wp-content/ 2>/dev/null; echo exit:$?"
        );
        $log("tar wp-content concluído.");

        return [$dbFile, $filesFile];
    }

    private function backupLaravel(SFTP $sftp, Server $server, string $tmpDir, callable $log): array
    {
        $appPath = rtrim($server->app_path, '/');
        $log("Laravel root: {$appPath}");

        // Read .env
        $envContent = $sftp->exec("cat {$appPath}/.env 2>/dev/null");
        $envVars    = [];
        foreach (explode("\n", $envContent) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $envVars[trim($k)] = trim($v, " \t\"'");
        }

        $dbHost = $envVars['DB_HOST'] ?? '127.0.0.1';
        $dbUser = $envVars['DB_USERNAME'] ?? '';
        $dbPass = $envVars['DB_PASSWORD'] ?? '';
        $dbName = $envVars['DB_DATABASE'] ?? '';

        if (! $dbName) {
            throw new \RuntimeException("Não foi possível ler DB_DATABASE do .env em {$appPath}");
        }

        $log("BD: {$dbName}@{$dbHost}");

        // DB dump
        $dbFile  = "{$tmpDir}/db.sql.gz";
        $dumpCmd = sprintf(
            "mysqldump -h%s -u%s -p%s %s 2>/dev/null | gzip > %s",
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            $dbFile
        );
        $sftp->exec($dumpCmd);
        $log("mysqldump concluído.");

        // Storage backup
        $storagePaths = $server->storage_paths ?? ['storage/app'];
        $pathArgs     = implode(' ', array_map('escapeshellarg', $storagePaths));
        $filesFile    = "{$tmpDir}/storage.tar.gz";
        $sftp->exec("tar -czf {$filesFile} -C {$appPath} {$pathArgs} 2>/dev/null");
        $log("tar storage concluído.");

        // .env backup
        $envFile = "{$tmpDir}/dot-env";
        $sftp->exec("cp {$appPath}/.env {$envFile}");

        return [$dbFile, $filesFile, $envFile];
    }

    private function backupPlesk(SFTP $sftp, Server $server, string $tmpDir, callable $log): array
    {
        $domain = $server->domain;
        if (! $domain) {
            throw new \RuntimeException("Servidor Plesk sem domínio configurado.");
        }

        // Check if real Plesk CLI is available
        $hasPleskBackup = trim($sftp->exec("test -x /usr/local/psa/bin/pleskbackup && echo yes || echo no"));

        if ($hasPleskBackup === 'yes') {
            return $this->backupPleskNative($sftp, $server, $tmpDir, $log);
        }

        // No Plesk CLI — server is a plain VPS. Auto-detect WordPress at standard paths.
        $log("pleskbackup não encontrado — a detectar WordPress para domínio {$domain}...");

        return $this->backupWordPressAutoDetect($sftp, $server, $domain, $tmpDir, $log);
    }

    private function backupPleskNative(SFTP $sftp, Server $server, string $tmpDir, callable $log): array
    {
        $domain     = $server->domain;
        $backupFile = "{$tmpDir}/plesk-{$domain}.tar";

        $log("Plesk backup (nativo) do domínio: {$domain}");

        $output = $sftp->exec(
            "/usr/local/psa/bin/pleskbackup domains {$domain} --skip-logs --output-file={$backupFile} 2>&1",
            3600
        );

        $check = trim($sftp->exec("test -f {$backupFile} && echo ok || echo missing"));
        if ($check !== 'ok') {
            throw new \RuntimeException(
                "pleskbackup não criou o ficheiro. Output: " . substr($output, 0, 300)
            );
        }

        $log("pleskbackup nativo concluído.");

        return [$backupFile];
    }

    private function backupWordPressAutoDetect(
        SFTP $sftp,
        Server $server,
        string $domain,
        string $tmpDir,
        callable $log
    ): array {
        // Try wp_root from DB first, then standard paths
        $candidates = array_filter([
            $server->wp_root,
            "/var/www/{$domain}",
            "/var/www/{$domain}/public_html",
            "/var/www/html",
        ]);

        $wpRoot = null;
        foreach ($candidates as $candidate) {
            $check = trim($sftp->exec("test -f {$candidate}/wp-config.php && echo yes || echo no"));
            if ($check === 'yes') {
                $wpRoot = rtrim($candidate, '/');
                break;
            }
        }

        if (! $wpRoot) {
            // Last resort: Apache DocumentRoot
            $docRoot = trim($sftp->exec(
                "grep -r 'DocumentRoot' /etc/apache2/sites-enabled/{$domain}*.conf 2>/dev/null | awk '{print \$2}' | head -1"
            ));
            if ($docRoot && trim($sftp->exec("test -f {$docRoot}/wp-config.php && echo yes || echo no")) === 'yes') {
                $wpRoot = rtrim($docRoot, '/');
            }
        }

        if (! $wpRoot) {
            throw new \RuntimeException(
                "Não foi possível encontrar o WordPress para {$domain}. " .
                "Configura wp_root na ficha do servidor."
            );
        }

        $log("WordPress encontrado em: {$wpRoot}");

        // Reuse WordPress backup logic
        return $this->backupWordPress($sftp, $server, $tmpDir, $log, $wpRoot);
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function nasDirectory(Server $server): string
    {
        $client     = $server->client?->name ?? 'sem-cliente';
        $serverName = $server->name;
        $date       = now()->format('Y-m-d');

        // Use raw names (NAS filesystem), not slugs — more readable on disk
        return $client . '/' . $serverName . '/' . $date;
    }

    private function cleanupTmp(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (glob("{$dir}/*") ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} B";
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 1) . ' MB';
    }
}
