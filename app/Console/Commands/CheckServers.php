<?php

namespace App\Console\Commands;

use App\Models\Server;
use Illuminate\Console\Command;

class CheckServers extends Command
{
    protected $signature = 'server:check {--id= : Verificar apenas este servidor (ID)}';

    protected $description = 'Verifica conectividade TCP (porta SSH) de todos os servidores ativos';

    public function handle(): int
    {
        $query = Server::query()->where('is_active', true)->whereNotNull('host');

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $servers = $query->get();

        if ($servers->isEmpty()) {
            $this->info('Nenhum servidor ativo encontrado.');
            return 0;
        }

        $this->info("A verificar {$servers->count()} servidor(es)...");

        $up   = 0;
        $down = 0;

        foreach ($servers as $server) {
            $port  = $server->port ?: 22;
            $host  = $server->host;
            $start = microtime(true);

            $conn = @fsockopen($host, $port, $errno, $errstr, 5);
            $ms   = (int) ((microtime(true) - $start) * 1000);

            if ($conn) {
                fclose($conn);
                $server->update([
                    'ping_status'          => 'up',
                    'ping_last_checked_at' => now(),
                    'ping_response_ms'     => $ms,
                    'ping_error'           => null,
                ]);
                $up++;
                $this->line(" ✓ {$server->name} ({$host}:{$port}) — {$ms}ms");
            } else {
                $error = $errstr ? "{$errstr} ({$errno})" : "timeout ({$errno})";
                $server->update([
                    'ping_status'          => 'down',
                    'ping_last_checked_at' => now(),
                    'ping_response_ms'     => $ms,
                    'ping_error'           => $error,
                ]);
                $down++;
                $this->line(" ✗ {$server->name} ({$host}:{$port}) — {$error}");
            }
        }

        $this->newLine();
        $this->info("Online: {$up}   Offline: {$down}");

        return 0;
    }
}
