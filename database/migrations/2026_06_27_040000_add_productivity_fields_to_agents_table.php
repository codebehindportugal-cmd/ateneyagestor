<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('agent_type')->default('backup')->after('slug');
            $table->string('computer_name')->nullable()->after('last_seen_at');
            $table->string('assigned_user_name')->nullable()->after('computer_name');
            $table->string('assigned_user_email')->nullable()->after('assigned_user_name');
            $table->string('department')->nullable()->after('assigned_user_email');
            $table->string('asset_tag')->nullable()->after('department');
            $table->boolean('productivity_monitor_enabled')->default(false)->after('asset_tag');
            $table->unsignedInteger('productivity_send_interval_seconds')->default(60)->after('productivity_monitor_enabled');
            $table->unsignedInteger('productivity_sample_interval_seconds')->default(5)->after('productivity_send_interval_seconds');
            $table->unsignedInteger('productivity_idle_threshold_seconds')->default(300)->after('productivity_sample_interval_seconds');
            $table->boolean('productivity_work_hours_enabled')->default(true)->after('productivity_idle_threshold_seconds');
            $table->string('productivity_work_start')->default('09:00')->after('productivity_work_hours_enabled');
            $table->string('productivity_work_end')->default('18:00')->after('productivity_work_start');
            $table->json('productivity_work_weekdays')->nullable()->after('productivity_work_end');
            $table->boolean('productivity_collect_domains')->default(false)->after('productivity_work_weekdays');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn([
                'agent_type',
                'computer_name',
                'assigned_user_name',
                'assigned_user_email',
                'department',
                'asset_tag',
                'productivity_monitor_enabled',
                'productivity_send_interval_seconds',
                'productivity_sample_interval_seconds',
                'productivity_idle_threshold_seconds',
                'productivity_work_hours_enabled',
                'productivity_work_start',
                'productivity_work_end',
                'productivity_work_weekdays',
                'productivity_collect_domains',
            ]);
        });
    }
};
