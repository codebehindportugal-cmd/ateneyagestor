<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A backup target. Holds connection METADATA ONLY -- hostnames,
        // ports, usernames, app paths, domains. Deliberately no SSH key /
        // cPanel token columns: those secrets live only in secrets.yaml on
        // the Pi, matched to this record via agent_secret_ref. See the
        // README "Arquitetura de seguranca" section for why.
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name')->unique(); // matches config.yaml's server "name" / folder on disk
            $table->string('type'); // vps_laravel | plesk | cpanel
            $table->boolean('is_active')->default(true);

            $table->string('host');
            $table->unsignedInteger('port')->nullable();
            $table->string('user')->nullable(); // SSH user (vps_laravel, plesk)

            // vps_laravel
            $table->string('app_path')->nullable();
            $table->json('storage_paths')->nullable();
            $table->json('db_override')->nullable();

            // plesk
            $table->string('domain')->nullable();
            $table->json('plesk_backup_args')->nullable();

            // cpanel
            $table->unsignedInteger('api_port')->nullable();
            $table->string('backup_dest')->nullable();
            $table->unsignedInteger('poll_interval_seconds')->nullable();
            $table->unsignedInteger('max_wait_seconds')->nullable();

            // Key into the Pi's local secrets.yaml. Defaults to `name` if
            // left blank (see agent_sync.py merge_secret()).
            $table->string('agent_secret_ref')->nullable();

            // Per-server retention override; falls back to the agent's
            // own global defaults (also editable from this site) if null.
            $table->unsignedInteger('retention_keep_days')->nullable();
            $table->unsignedInteger('retention_keep_min_copies')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
