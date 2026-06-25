<?php

namespace App\Console\Commands;

use App\Enums\SecurityStatus;
use App\Models\Server;
use App\Services\SecurityScanService;
use Illuminate\Console\Command;

class RunSecurityScan extends Command
{
    protected $signature = 'security:scan
        {--server= : ID ou nome do servidor}
        {--all    : Scan de todos os servidores activos com chave SSH}';

    protected $description = 'Análise de segurança de servidores via SSH (webshells, processos, SMTP, rkhunter)';

    public function handle(SecurityScanService $scanner): int
    {
        $servers = $this->resolveServers();

        if ($servers->isEmpty()) {
            $this->error('Nenhum servidor encontrado. Usa --all ou --server=ID/nome.');

            return 1;
        }

        $this->info("Servidores a analisar: {$servers->count()}");
        $this->newLine();

        $ok = $warning = $critical = $failed = 0;

        foreach ($servers as $server) {
            $keyPath = $server->ssh_key_path ?: config('backup.ssh_key');

            if (! $keyPath) {
                $this->warn("  {$server->name}: sem chave SSH configurada — a ignorar.");
                continue;
            }

            $this->line("<fg=cyan>▶ {$server->name}</> ({$server->host}) — {$server->client?->name}");

            $scan = $scanner->scan(
                $server,
                'command',
                fn (string $msg) => $this->line("  {$msg}")
            );

            match ($scan->status) {
                SecurityStatus::Clean    => [$this->line("  <fg=green>✓ Limpo</>"), $ok++],
                SecurityStatus::Warning  => [$this->line("  <fg=yellow>⚠ Aviso — {$scan->findings_count} achado(s)</>"), $warning++],
                SecurityStatus::Critical => [$this->line("  <fg=red>✗ CRÍTICO — {$scan->findings_count} achado(s)</>"), $critical++],
                default                  => [$this->line("  <fg=gray>✗ Falhou: {$scan->error}</>"), $failed++],
            };

            $this->newLine();
        }

        $this->newLine();
        $this->info("Resumo — ✓ Limpo: {$ok}   ⚠ Aviso: {$warning}   ✗ Crítico: {$critical}   ? Falhou: {$failed}");

        return ($critical + $failed) > 0 ? 1 : 0;
    }

    private function resolveServers()
    {
        $query = Server::with('client')->where('is_active', true);

        if ($this->option('all')) {
            return $query->get();
        }

        if ($serverId = $this->option('server')) {
            return $query->where(function ($q) use ($serverId) {
                $q->where('id', $serverId)->orWhere('name', $serverId);
            })->get();
        }

        return collect();
    }
}
