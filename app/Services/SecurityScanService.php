<?php

namespace App\Services;

use App\Enums\SecurityStatus;
use App\Enums\ServerType;
use App\Models\SecurityScan;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;

class SecurityScanService
{
    public function scan(Server $server, string $triggeredBy = 'command', ?callable $log = null): SecurityScan
    {
        $logger = $log ?? fn (string $msg) => Log::info("[security:{$server->name}] {$msg}");

        $scan = SecurityScan::create([
            'server_id'    => $server->id,
            'status'       => SecurityStatus::Running->value,
            'started_at'   => now(),
            'triggered_by' => $triggeredBy,
        ]);

        $logLines   = [];
        $captureLog = function (string $msg) use (&$logLines, $logger) {
            $logLines[] = '[' . now()->format('H:i:s') . '] ' . $msg;
            $logger($msg);
        };

        try {
            $captureLog("A iniciar scan de segurança de {$server->name}...");

            $keyPath = $server->ssh_key_path ?: config('backup.ssh_key');

            if (! $keyPath || ! file_exists($keyPath)) {
                throw new \RuntimeException(
                    "Chave SSH não encontrada: {$keyPath}. Define BACKUP_SSH_KEY no .env."
                );
            }

            $key  = PublicKeyLoader::load(file_get_contents($keyPath));
            $sftp = new SFTP($server->host, $server->port ?? 22);
            $sftp->setTimeout(600);

            if (! $sftp->login($server->user ?? 'root', $key)) {
                throw new \RuntimeException("Autenticação SSH falhou em {$server->host}.");
            }

            $captureLog("Ligado a {$server->host}.");

            $findings = $this->runChecks($sftp, $server, $captureLog);

            $sftp->disconnect();

            $status        = $this->calculateStatus($findings);
            $findingsCount = count(array_filter($findings, fn ($f) => $f['has_findings'] ?? false));

            $captureLog("Scan concluído: {$status->label()}. {$findingsCount} check(s) com achados.");

            $scan->update([
                'status'         => $status->value,
                'findings_count' => $findingsCount,
                'findings'       => $findings,
                'log'            => implode("\n", $logLines),
                'finished_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            $captureLog("ERRO: " . $e->getMessage());

            $scan->update([
                'status'      => SecurityStatus::Failed->value,
                'error'       => $e->getMessage(),
                'log'         => implode("\n", $logLines),
                'finished_at' => now(),
            ]);
        }

        return $scan->fresh();
    }

    // -------------------------------------------------------------------------

    private function runChecks(SFTP $sftp, Server $server, callable $log): array
    {
        $webRoot  = $this->getWebRoot($server);
        $findings = [];

        $log("Directório de pesquisa: {$webRoot}");

        $log("(1/7) A verificar ficheiros PHP suspeitos...");
        $findings[] = $this->checkPhpWebshells($sftp, $webRoot);

        $log("(2/7) A verificar ficheiros modificados recentemente...");
        $findings[] = $this->checkRecentlyModified($sftp, $webRoot);

        $log("(3/7) A verificar processos suspeitos...");
        $findings[] = $this->checkSuspiciousProcesses($sftp);

        $log("(4/7) A verificar ligações SMTP...");
        $findings[] = $this->checkSmtpConnections($sftp);

        $log("(5/7) A verificar cron jobs...");
        $findings[] = $this->checkCronJobs($sftp);

        $log("(6/7) A verificar logins recentes...");
        $findings[] = $this->checkRecentLogins($sftp);

        $log("(7/7) A verificar rkhunter...");
        $findings[] = $this->checkRkhunter($sftp);

        return $findings;
    }

    private function checkPhpWebshells(SFTP $sftp, string $webRoot): array
    {
        // Patterns common in real webshells — eval(base64_decode), a shell-exec
        // function fed directly from a superglobal, and $_GET/POST-fed
        // file_put_contents. The /e preg_replace modifier was dropped (removed in
        // PHP 7, so that check was pure noise). Command-exec functions require a
        // superglobal argument so they don't match method calls (->passthru),
        // wrapper fns (fpassthru/gzpassthru), or hardcoded-argument calls in
        // vendor code — only direct user-input-to-shell is flagged.
        $pattern = 'eval\s*\(\s*base64_decode|assert\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)|(system|exec|shell_exec|passthru|proc_open)\s*\([^)]*\$_(GET|POST|REQUEST|COOKIE)|file_put_contents\s*\([^,]+,\s*\$_(GET|POST|REQUEST)';

        // vendor/node_modules are Composer/npm-managed, not attacker-writable, and
        // their source legitimately contains fragments of these patterns (function
        // names, docblocks) — excluding them removes noise without losing coverage
        // of the actual application/theme/plugin code. This file itself is also
        // excluded since it literally contains the pattern text below as a string.
        $exclude = "-not -path '*/vendor/*' -not -path '*/node_modules/*'" .
            " -not -path '*/app/Services/SecurityScanService.php'";

        $raw = (string) $sftp->exec(
            "find " . escapeshellarg($webRoot) . " -name '*.php' -type f {$exclude} 2>/dev/null" .
            " | xargs grep -lP " . escapeshellarg($pattern) . " 2>/dev/null | head -20",
            60
        );

        $items = array_values(array_filter(explode("\n", trim($raw))));

        return [
            'check'        => 'php_webshells',
            'label'        => 'Ficheiros PHP suspeitos (eval/base64/passthru)',
            'severity'     => 'critical',
            'count'        => count($items),
            'items'        => $items,
            'raw'          => $raw,
            'has_findings' => count($items) > 0,
        ];
    }

    private function checkRecentlyModified(SFTP $sftp, string $webRoot): array
    {
        // Exclude Composer/npm dirs (churn on every deploy) and Laravel's compiled
        // view/route cache (rewritten on every cache:clear, not "modified" in any
        // meaningful sense) — otherwise every deploy floods this with noise.
        $exclude = "-not -path '*/vendor/*' -not -path '*/node_modules/*'" .
            " -not -path '*/storage/framework/*' -not -path '*/bootstrap/cache/*'";

        $raw = (string) $sftp->exec(
            "find " . escapeshellarg($webRoot) . " -name '*.php' -type f -mtime -7 {$exclude} 2>/dev/null | head -30",
            60
        );

        $items = array_values(array_filter(explode("\n", trim($raw))));
        $count = count($items);

        // More than 15 PHP files changed in 7 days is suspicious on a static site;
        // on an actively-developed site it's normal — admin should review the list.
        return [
            'check'        => 'recently_modified_php',
            'label'        => 'Ficheiros PHP modificados nos últimos 7 dias',
            'severity'     => 'warning',
            'count'        => $count,
            'items'        => $items,
            'raw'          => $raw,
            'has_findings' => $count > 15,
        ];
    }

    private function checkSuspiciousProcesses(SFTP $sftp): array
    {
        // Look for processes running from /tmp or /dev/shm, or interpreters with
        // inline code that web processes shouldn't spawn.
        $raw = (string) $sftp->exec(
            "ps aux --no-header 2>/dev/null | " .
            "grep -E '(/tmp/[a-zA-Z0-9_-]+[[:space:]]|/dev/shm/|perl -e[[:space:]]|\bpython3? -c[[:space:]]\"|\bwget -O-[[:space:]])' | " .
            "grep -v grep | head -10",
            30
        );

        $items = array_values(array_filter(explode("\n", trim($raw))));

        return [
            'check'        => 'suspicious_processes',
            'label'        => 'Processos suspeitos',
            'severity'     => 'warning',
            'count'        => count($items),
            'items'        => $items,
            'raw'          => $raw,
            'has_findings' => count($items) > 0,
        ];
    }

    private function checkSmtpConnections(SFTP $sftp): array
    {
        $raw = (string) $sftp->exec(
            "ss -tn state established '( dport = :25 )' 2>/dev/null | tail -n +2",
            30
        );

        $items = array_values(array_filter(explode("\n", trim($raw))));

        return [
            'check'        => 'smtp_connections',
            'label'        => 'Ligações SMTP externas activas (porta 25)',
            'severity'     => 'critical',
            'count'        => count($items),
            'items'        => $items,
            'raw'          => $raw,
            'has_findings' => count($items) > 0,
        ];
    }

    private function checkCronJobs(SFTP $sftp): array
    {
        $raw = (string) $sftp->exec(
            "(crontab -l 2>/dev/null; " .
            "for f in /etc/cron.d/* /etc/cron.hourly/* /etc/cron.daily/* /etc/cron.weekly/*; do " .
            "  [ -f \"\$f\" ] && printf '\\n=== %s ===\\n' \"\$f\" && cat \"\$f\"; " .
            "done) 2>/dev/null | grep -v '^#' | grep -v '^[[:space:]]*$' | head -50",
            30
        );

        $items = array_values(array_filter(explode("\n", trim($raw))));

        return [
            'check'        => 'cron_jobs',
            'label'        => 'Cron jobs activos',
            'severity'     => 'info',
            'count'        => count($items),
            'items'        => $items,
            'raw'          => $raw,
            'has_findings' => false, // informational only — human review required
        ];
    }

    private function checkRecentLogins(SFTP $sftp): array
    {
        $raw = (string) $sftp->exec("last -n 20 2>/dev/null | head -20", 30);

        $items = array_values(array_filter(explode("\n", trim($raw))));

        return [
            'check'        => 'recent_logins',
            'label'        => 'Logins recentes no servidor',
            'severity'     => 'info',
            'count'        => count($items),
            'items'        => $items,
            'raw'          => $raw,
            'has_findings' => false,
        ];
    }

    private function checkRkhunter(SFTP $sftp): array
    {
        $available = trim((string) $sftp->exec("which rkhunter 2>/dev/null", 10));

        if (! $available) {
            return [
                'check'        => 'rkhunter',
                'label'        => 'rkhunter (rootkit hunter)',
                'severity'     => 'info',
                'count'        => 0,
                'items'        => [],
                'raw'          => '(não instalado — instala com: apt install rkhunter)',
                'has_findings' => false,
            ];
        }

        // --sk skips keypress prompts; --nocolors removes ANSI codes
        $raw = (string) $sftp->exec(
            "rkhunter --check --sk --nocolors 2>&1 | grep -E '(Warning|Rootkit detected|INFECTED)' | head -30",
            300
        );

        $items = array_values(array_filter(explode("\n", trim($raw))));

        return [
            'check'        => 'rkhunter',
            'label'        => 'rkhunter (rootkit hunter)',
            'severity'     => 'critical',
            'count'        => count($items),
            'items'        => $items,
            'raw'          => $raw ?: '(sem avisos)',
            'has_findings' => count($items) > 0,
        ];
    }

    // -------------------------------------------------------------------------

    private function getWebRoot(Server $server): string
    {
        return match ($server->type) {
            ServerType::WordPress  => rtrim($server->wp_root ?? '/var/www', '/'),
            ServerType::VpsLaravel => rtrim(dirname($server->app_path ?? '/var/www/app'), '/'),
            default                => '/var/www/vhosts/' . ltrim($server->domain ?? '', '/'),
        };
    }

    private function calculateStatus(array $findings): SecurityStatus
    {
        foreach ($findings as $f) {
            if (($f['has_findings'] ?? false) && ($f['severity'] ?? '') === 'critical') {
                return SecurityStatus::Critical;
            }
        }

        foreach ($findings as $f) {
            if (($f['has_findings'] ?? false) && ($f['severity'] ?? '') === 'warning') {
                return SecurityStatus::Warning;
            }
        }

        return SecurityStatus::Clean;
    }
}
