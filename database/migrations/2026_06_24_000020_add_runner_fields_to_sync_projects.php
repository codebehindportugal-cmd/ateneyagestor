<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_projects', function (Blueprint $table) {
            // Path to the script relative to the project base_path()
            // e.g. syncer/wintouch_woo/main.py
            $table->string('runner_script_path')->nullable()->after('host');
            // Cron expression for automatic scheduling, e.g. "0 */3 * * *"
            $table->string('runner_schedule')->nullable()->after('runner_script_path');
        });
    }

    public function down(): void
    {
        Schema::table('sync_projects', function (Blueprint $table) {
            $table->dropColumn(['runner_script_path', 'runner_schedule']);
        });
    }
};
