<?php

namespace App\Console\Commands;

use App\Models\Server;
use Illuminate\Console\Command;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;

class DiscoverWpRoots extends Command
{
    protected $signature = 'server:wp-roots:discover
        {--dry-run : Mostra o que seria alterado sem gravar na BD}';

    protected $description = 'Descobre wp_root via SSH para todos os servidores Plesk/WordPress sem wp_root configurado';

    public function handle(): int
    {
        $servers = Server::where('is_active', true)
            ->whereNull('wp_root')
            ->whereIn('type', ['plesk', 'wordpress'])
            ->with('client')
            ->get();

        if ($servers->isEmpty()) {
            $this->info('Todos os servidores já têm wp_root configurado.');
            return 0;
        }

        $this->info("Servidores sem wp_root: {$servers->count()}");
        $this->newLine();

        $updated = $notFound = $errors = 0;

        foreach ($servers as $server) {
            $this->line("<fg=cyan>▶ {$server->name}</> ({$server->host}) — {$server->client?->name}");

            try {
                [$wpRoot, $allPaths] = $this->discoverWpRoot($server);

                if ($wpRoot) {
                    $this->line("  <fg=green>✓ Encontrado: {$wpRoot}</>");

                    if (count($allPaths) > 1) {
                        $this->line("  <fg=yellow>  (outros: " . implode(', ', array_diff($allPaths, [$wpRoot])) . ")</>");
                    }

                    if (! $this->option('dry-run')) {
                        $server->update(['wp_root' => $wpRoot]);
                        $this->line("  <fg=green>  → wp_root gravado na BD.</>");
                    } else {
                        $this->line("  <fg=yellow>  → [dry-run] não gravado.</>");
                    }

                    $updated++;
                } else {
                    $this->warn("  ⚠ wp-config.php não encontrado em {$server->host}.");
                    $notFound++;
                }
            } catch (\Throwable $e) {
                $this->error("  ✗ Erro: " . $e->getMessage());
                $errors++;
            }

            $this->newLine();
        }

        $this->info("Resumo — ✓ Actualizados: {$updated}   ⚠ Não encontrado: {$notFound}   ✗ Erros: {$errors}");

        return $errors > 0 ? 1 : 0;
    }

    private function discoverWpRoot(Server $server): array
    {
        $keyPath = config('backup.ssh_key') ?: $server->ssh_key_path;

        if (! $keyPath || ! file_exists($keyPath)) {
            throw new \RuntimeException(
                "Chave SSH não encontrada: {$keyPath}. Define BACKUP_SSH_KEY no .env."
            );
        }

        $key  = PublicKeyLoader::load(file_get_contents($keyPath));
        $sftp = new SFTP($server->host, $server->port ?? 22);
        $sftp->setTimeout(60);

        if (! $sftp->login($server->user ?? 'root', $key)) {
            throw new \RuntimeException("Autenticação SSH falhou em {$server->host}.");
        }

        // Search all common web roots: Plesk (/var/www/vhosts), generic (/var/www/{domain}),
        // cPanel (/home). Exclude wp-content copies placed by cache/backup plugins.
        $raw = (string) $sftp->exec(
            "find /var/www /home -name 'wp-config.php' -not -path '*/wp-content/*' 2>/dev/null | head -15"
        );
        $sftp->disconnect();

        $paths = array_values(array_filter(
            array_map('dirname', array_filter(explode("\n", trim($raw))))
        ));

        if (empty($paths)) {
            return [null, []];
        }

        if (count($paths) === 1) {
            return [$paths[0], $paths];
        }

        // Multiple installs on this host: pick the one whose path contains the domain stem.
        // e.g. domain=alorfisconta.com → stem=alorfisconta → matches alvorfisconta.pt/httpdocs
        $domain = $server->domain ?? '';
        $stem   = preg_replace('/\.[a-z]{2,}$/', '', preg_replace('/^www\./', '', $domain));

        if ($stem) {
            foreach ($paths as $path) {
                if (str_contains($path, $stem)) {
                    return [$path, $paths];
                }
            }
        }

        // Fall back: first result (alphabetically shortest path wins)
        usort($paths, fn ($a, $b) => strlen($a) <=> strlen($b));
        $this->warn("  Múltiplos resultados — a usar o mais curto. Verifica manualmente.");

        return [$paths[0], $paths];
    }
}
