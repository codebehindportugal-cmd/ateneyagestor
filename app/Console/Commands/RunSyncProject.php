<?php

namespace App\Console\Commands;

use App\Enums\SyncStatus;
use App\Models\SyncProject;
use App\Models\SyncRun;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RunSyncProject extends Command
{
    protected $signature = 'sync:run
        {slug : Slug do SyncProject a executar}
        {--run-id= : ID de SyncRun ja criado pelo painel}';

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

        $this->markStaleRunsAsFailed($project);

        $runId = (int) ($this->option('run-id') ?: 0);

        // Prevent concurrent runs
        $running = SyncRun::where('sync_project_id', $project->id)
            ->where('status', 'running')
            ->when($runId > 0, fn ($query) => $query->where('id', '!=', $runId))
            ->where('started_at', '>', now()->subHours(2))
            ->exists();

        if ($running) {
            $this->warn("Sync '{$slug}' já está em execução. A ignorar.");
            return 0;
        }

        $run = $runId > 0
            ? SyncRun::where('sync_project_id', $project->id)->find($runId)
            : null;

        if (! $run) {
            $run = SyncRun::create([
                'sync_project_id' => $project->id,
                'status'          => SyncStatus::Running,
                'started_at'      => now(),
            ]);
        } else {
            $run->update([
                'status'      => SyncStatus::Running,
                'started_at'  => $run->started_at ?: now(),
                'finished_at' => null,
                'log'         => trim((string) $run->log) . "\n[" . now()->format('H:i:s') . "] Processo Laravel sync:run iniciado.\n",
            ]);
        }

        $project->update(['status' => 'running']);

        $this->info("[sync:{$slug}] A iniciar — {$scriptPath}");

        try {
            $scriptDir = dirname($scriptPath);
            $python    = $this->detectPython($scriptDir);
            $cmd       = [$python, '-u', $scriptPath];

            $configPath = $this->writeProjectConfig($project, $run);
            if ($configPath) {
                $cmd[] = $configPath;
            }

            // Ensure the Python script's logs/ directory exists and is writable.
            // If a previous root-cron run created it, the web user can't write — but
            // mkdir here at least handles the "doesn't exist yet" case gracefully.
            $logsDir = $scriptDir . '/logs';
            if (! is_dir($logsDir)) {
                mkdir($logsDir, 0775, true);
            }

            $process = new Process($cmd, $scriptDir, [
                'PYTHONIOENCODING' => 'utf-8',
                'PYTHONUTF8'       => '1',
                'PYTHONUNBUFFERED' => '1',
            ], null, 7200); // 2 hours — full catalogue sync can exceed 1 hour

            $liveOutput = '';
            $lastFlushAt = microtime(true);

            $process->run(function (string $type, string $buffer) use ($run, &$liveOutput, &$lastFlushAt) {
                $this->getOutput()->write($buffer);

                $liveOutput .= $this->cleanForDatabase($buffer);

                if (strlen($liveOutput) > 65000) {
                    $liveOutput = $this->trimForDatabase($liveOutput, 65000);
                }

                if ((microtime(true) - $lastFlushAt) >= 5 || strlen($buffer) > 8192) {
                    $run->update([
                        'log' => $liveOutput,
                    ]);
                    $lastFlushAt = microtime(true);
                }
            });

            $output   = $this->cleanForDatabase($process->getOutput() . $process->getErrorOutput());
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
            $message = $this->cleanForDatabase($e->getMessage());
            $this->finishRun($run, $project, SyncStatus::Failed, 'error', $message, 0, 0, 0);
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
        $error = $this->cleanForDatabase((string) $error);
        $log = $this->cleanForDatabase($log);

        $run->update([
            'status'           => $enumStatus,
            'products_synced'  => $products,
            'orders_synced'    => $orders,
            'errors_count'     => $errors,
            'error'            => $this->trimForDatabase($error, 8000),
            'log'              => $this->trimForDatabase($log, 65000),
            'finished_at'      => now(),
        ]);

        $project->update([
            'status'      => $projectStatus,
            'last_run_at' => now(),
        ]);
    }

    private function detectPython(string $dir): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            foreach (["{$dir}/venv/Scripts/python.exe", "{$dir}/.venv/Scripts/python.exe"] as $venvPython) {
                if (is_file($venvPython)) {
                    return $venvPython;
                }
            }

            return 'python';
        }

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

    private function writeProjectConfig(SyncProject $project, SyncRun $run): ?string
    {
        if (! $project->hasLocalApiConfig()) {
            return null;
        }

        $dir = storage_path('app/sync-configs');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $path = $dir . "/sync-project-{$project->id}-run-{$run->id}.json";
        file_put_contents(
            $path,
            json_encode($project->toRunnerConfig(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return $path;
    }

    private function cleanForDatabase(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_check_encoding') && ! mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        $value = @iconv('UTF-8', 'UTF-8//IGNORE', $value) ?: $value;

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    }

    private function trimForDatabase(string $value, int $maxBytes): string
    {
        $value = $this->cleanForDatabase($value);

        if (strlen($value) <= $maxBytes) {
            return $value;
        }

        return $this->cleanForDatabase(substr($value, -$maxBytes));
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

    private function markStaleRunsAsFailed(SyncProject $project): void
    {
        $staleRuns = SyncRun::where('sync_project_id', $project->id)
            ->where('status', 'running')
            ->where('started_at', '<=', now()->subHours(2))
            ->get();

        if ($staleRuns->isEmpty()) {
            return;
        }

        foreach ($staleRuns as $staleRun) {
            $staleRun->update([
                'status'       => SyncStatus::Failed,
                'errors_count' => max(1, (int) $staleRun->errors_count),
                'error'        => 'Execução ficou presa ou excedeu 2 horas sem terminar.',
                'finished_at'  => now(),
            ]);
        }

        $project->update([
            'status'      => 'error',
            'last_run_at' => now(),
        ]);
    }
}
