<?php

namespace App\Jobs;

use App\Models\Server;
use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunServerBackup implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 3700;

    public function __construct(public int $serverId, public string $triggeredBy = 'filament')
    {
    }

    public function handle(BackupService $backup): void
    {
        $server = Server::findOrFail($this->serverId);

        $backup->backup($server, $this->triggeredBy);
    }
}
