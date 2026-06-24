<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\BackupService;
use Illuminate\Console\Command;

class RunBackups extends Command
{
    protected $signature = 'backup:run
        {--server= : ID ou nome do servidor (pode repetir: --server=1 --server=2)}
        {--client= : ID do cliente — faz backup de todos os seus servidores}
        {--all    : Faz backup de todos os servidores activos}
        {--type=  : Filtra por tipo (wordpress, plesk, vps_laravel)}';

    protected $description = 'Cria backups de servidores e envia para o NAS';

    public function handle(BackupService $backupService): int
    {
        $servers = $this->resolveServers();

        if ($servers->isEmpty()) {
            $this->error('Nenhum servidor encontrado com os filtros fornecidos.');
            $this->line('Usa --all para todos, --server=ID para um específico, --client=ID para um cliente.');

            return 1;
        }

        $this->info("Servidores a fazer backup: {$servers->count()}");
        $this->newLine();

        $ok      = 0;
        $failed  = 0;
        $results = [];

        foreach ($servers as $server) {
            $this->line("<fg=cyan>▶ {$server->name}</> ({$server->type->value}) — {$server->client?->name}");

            $run = $backupService->backup(
                $server,
                'command',
                fn (string $msg) => $this->line("  {$msg}")
            );

            $results[] = [
                'Servidor' => $server->name,
                'Cliente'  => $server->client?->name ?? '-',
                'Estado'   => $run->status->label(),
                'Tamanho'  => $run->size_bytes ? $this->humanSize($run->size_bytes) : '-',
                'NAS'      => $run->nas_path ?? '(sem NAS)',
                'Erro'     => $run->error ? substr($run->error, 0, 60) : '',
            ];

            if ($run->status->value === 'success') {
                $ok++;
                $this->line("  <fg=green>✓ OK</>");
            } else {
                $failed++;
                $this->line("  <fg=red>✗ Falhou: {$run->error}</>");
            }

            $this->newLine();
        }

        $this->newLine();
        $this->info('Resumo:');
        $this->table(array_keys($results[0]), $results);

        $this->newLine();
        $this->info("✓ Sucesso: {$ok}   ✗ Falhou: {$failed}");

        return $failed > 0 ? 1 : 0;
    }

    private function resolveServers()
    {
        $query = Server::with('client')->where('is_active', true);

        if ($type = $this->option('type')) {
            $query->where('type', $type);
        }

        if ($this->option('all')) {
            return $query->get();
        }

        if ($clientId = $this->option('client')) {
            return $query->where('client_id', $clientId)->get();
        }

        $serverIds = (array) $this->option('server');
        if ($serverIds) {
            return $query->where(function ($q) use ($serverIds) {
                $q->whereIn('id', $serverIds)->orWhereIn('name', $serverIds);
            })->get();
        }

        $this->error('Especifica --all, --server=ID ou --client=ID.');

        return collect();
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 1) . ' MB';
    }
}
