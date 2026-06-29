<?php

namespace App\Models;

use App\Enums\SyncStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'sync_project_id',
        'status',
        'products_synced',
        'orders_synced',
        'errors_count',
        'error',
        'started_at',
        'finished_at',
        'log',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => SyncStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function syncProject(): BelongsTo
    {
        return $this->belongsTo(SyncProject::class);
    }

    public function durationSeconds(): ?int
    {
        if (! $this->started_at || ! $this->finished_at) {
            return null;
        }

        return (int) $this->started_at->diffInSeconds($this->finished_at);
    }

    public function elapsedSeconds(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        return (int) $this->started_at->diffInSeconds($this->finished_at ?? now());
    }

    public function liveLogPath(): ?string
    {
        $candidates = [];
        $project = $this->syncProject;

        if ($project?->slug) {
            $candidates[] = storage_path("logs/sync-{$project->slug}.log");
            $candidates[] = storage_path("logs/sync-{$project->slug}.error.log");
            $candidates[] = storage_path("logs/sync-{$project->slug}.launcher.log");
        }

        if ($project?->runner_script_path) {
            $scriptDir = base_path(dirname($project->runner_script_path));
            $candidates[] = $scriptDir.'/logs/wintouch_sync.log';
            $candidates[] = $scriptDir.'/logs/phc_woo_sync.log';
            $candidates[] = $scriptDir.'/sync.log';
            $candidates = array_merge($candidates, glob($scriptDir.'/logs/*.log') ?: []);
        }

        if ($project?->type === 'phc_woo') {
            $phcDir = dirname(base_path()).DIRECTORY_SEPARATOR.'phc_woo_sync';
            $candidates[] = $phcDir.'/sync.log';
            $candidates[] = $phcDir.'/logs/phc_woo_sync.log';
            $candidates = array_merge($candidates, glob($phcDir.'/logs/*.log') ?: []);
        }

        $candidates = array_values(array_unique(array_filter($candidates)));

        usort($candidates, fn (string $a, string $b) => (file_exists($b) ? filemtime($b) : 0) <=> (file_exists($a) ? filemtime($a) : 0));

        foreach ($candidates as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    public function liveLog(int $limit = 65000): ?string
    {
        $path = $this->liveLogPath();

        if (! $path) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        return substr($contents, -$limit);
    }
}
