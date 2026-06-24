<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('ping_status')->default('unknown')->after('is_active');
            $table->timestamp('ping_last_checked_at')->nullable()->after('ping_status');
            $table->unsignedInteger('ping_response_ms')->nullable()->after('ping_last_checked_at');
            $table->string('ping_error', 500)->nullable()->after('ping_response_ms');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['ping_status', 'ping_last_checked_at', 'ping_response_ms', 'ping_error']);
        });
    }
};
