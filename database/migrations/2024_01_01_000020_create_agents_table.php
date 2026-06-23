<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // An Agent is a Pi (or any machine) running agent_sync.py. Its
        // Sanctum token (personal_access_tokens, tokenable = Agent) only
        // grants access to /api/agent/* -- never an admin/client login.
        // last_seen_at/status are purely informational (set by the
        // heartbeat endpoint + routes/console.php's stale check) and never
        // gate whether backups run -- the Pi works independently of this
        // site being reachable.
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('offline'); // online | offline
            $table->timestamp('last_seen_at')->nullable();

            // Global settings for THIS Pi's run -- these belong here (not on
            // a site-wide settings table) because backup_root is a local
            // disk path on this specific machine, and each Pi could in
            // principle have different retention/notification preferences.
            // Sent back to the Pi as the "global" block of GET /api/agent/config.
            $table->string('backup_root')->default('/mnt/backup-disk');
            $table->unsignedInteger('retention_keep_days')->default(14);
            $table->unsignedInteger('retention_keep_min_copies')->default(3);
            $table->string('log_level')->default('INFO');
            $table->boolean('notify_webhook_enabled')->default(false);
            $table->string('notify_webhook_url')->nullable();
            $table->boolean('notify_sendmail_enabled')->default(false);
            $table->string('notify_sendmail_to')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
