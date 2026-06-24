<?php

namespace App\Console\Commands;

use App\Enums\SyncStatus;
use App\Models\SyncProject;
use App\Models\SyncRun;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RunSyncProject extends Command
{
    protected $signature = 'sync:run {slug : Slug do SyncProject a executar}';

    protected $description = 'Executa um sincronizador local e regista o resultado na BD';

    public function handle(): int
    {
        $slug    = $this->argument('slug');
        $project = SyncProject::where('slug', $slug)->where('is_active', true)->first();

        if (! $project) {
            $this->error("SyncProject '{$slug}' não encontrado ou inativo.");
            return 1;
        }

        if (! $project->runner_script_path) {
            $this->error("SyncProject '{$slug}' não tem runner_script_path configurado.");
            return 1;
        }

        $scriptPath = base_path($project->runner_script_path);

        if (! file_exists($scriptPath)) {
            $this->error("Script não encontrado: {$scriptPath}");
            return 1;
        }

        // Prevent concurrent runs
        $running = SyncRun::where('sync_project_id', $project->id)
            ->where('status', 'running')
            ->where('started_at', '>', now()->subHours(2))
            ->exists();

        if ($running) {
            $this->warn("Sync '{$slug}' já está em execução. A ignorar.");
            return 0;
        }

        $run = SyncRun::create([
            'sync_project_id' => $project->id,
            'status'          => SyncStatus::Running,
            'started_at'      => now(),
        ]);

        $project->update(['status' => 'running']);

        $this->info("[sync:{$slug}] A iniciar — {$scriptPath}");

        try {
            $scriptDir = dirname($scriptPath);
            $python    = $this->detectPython($scriptDir);
            $cmd       = [$python, $scriptPath];

            $process = new Process($cmd, $scriptDir, null, null, 3600);

            $process->run(function (string $type, string $buffer) {
                $this->getOutput()->write($buffer);
            });

            $output   = $process->getOutput() . $process->getErrorOutput();
            $exitCode = $process->getExitCode();

            [$products, $orders, $errors] = $this->parseStats($output);

            $enumStatus = ($exitCode === 0 && $errors === 0) ? SyncStatus::Success : SyncStatus::Failed;
            $status     = $enumStatus === SyncStatus::Success ? 'ok' : 'error';
            $error      = $exitCode !== 0 ? "Exit code {$exitCode}" : null;

            if ($errors > 0) {
                $error = ($error ? $error . ' | ' : '') . "{$errors} erros na sincronização";
            }

            $this->finishRun($run, $project, $enumStatus, $status, $error, $products, $orders, $errors, $output);

            $icon = $enumStatus === SyncStatus::Success ? '✓' : '✗';
            $this->info("[sync:{$slug}] {$icon} {$status} — produtos:{$products} encomendas:{$orders} erros:{$errors}");

            return $exitCode === 0 ? 0 : 1;
        } catch (\Throwable $e) {
            $this->finishRun($run, $project, SyncStatus::Failed, 'error', $e->getMessage(), 0, 0, 0);
            $this->error("[sync:{$slug}] Excepção: " . $e->getMessage());
            return 1;
        }
    }

    private function finishRun(
        SyncRun $run,
        SyncProject $project,
        SyncStatus $enumStatus,
        string $projectStatus,
        ?string $error,
        int $products,
        int $orders,
        int $errors,
        string $log = ''
    ): void {
        $run->update([
            'status'           => $enumStatus,
            'products_synced'  => $products,
            'orders_synced'    => $orders,
            'errors_count'     => $errors,
            'error'            => $error,
            'log'              => substr($log, -65000),
            'finished_at'      => now(),
        ]);

        $project->update([
            'status'      => $projectStatus,
            'last_run_at' => now(),
        ]);
    }

    private function detectPython(string $dir): string
    {
        // file_exists() and is_link() both follow symlinks for open_basedir checks.
        // venv/bin/python3 is a symlink → /usr/bin/python3 (outside allowed path) → E_WARNING.
        // Check for the bin/ DIRECTORY instead: it's a real dir, not a symlink, so
        // is_dir() works safely. The spawned Process has no open_basedir restriction.
        foreach (["{$dir}/venv", "{$dir}/.venv"] as $venvDir) {
            if (is_dir("{$venvDir}/bin")) {
                return "{$venvDir}/bin/python3";
            }
        }
        return '/usr/bin/python3';
    }

    // Parses a line like: SYNC_RESULT:{"products_synced":10,"orders_synced":5,"errors_count":0}
    private function parseStats(string $output): array
    {
        if (preg_match('/SYNC_RESULT:(\{.+?\})/m', $output, $m)) {
            $data = json_decode($m[1], true) ?? [];
            return [
                (int) ($data['products_synced'] ?? 0),
                (int) ($data['orders_synced']   ?? 0),
                (int) ($data['errors_count']    ?? 0),
            ];
        }
        return [0, 0, 0];
    }
}
