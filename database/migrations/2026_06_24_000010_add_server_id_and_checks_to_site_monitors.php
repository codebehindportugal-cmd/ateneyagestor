<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_monitors', function (Blueprint $table) {
            $table->foreignId('server_id')->nullable()->after('client_id')
                ->constrained()->nullOnDelete();
        });

        Schema::create('site_monitor_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_monitor_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20); // up | down
            $table->unsignedSmallInteger('http_code')->nullable();
            $table->unsignedInteger('response_ms')->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamp('checked_at');

            $table->index(['site_monitor_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_monitor_checks');
        Schema::table('site_monitors', function (Blueprint $table) {
            $table->dropForeign(['server_id']);
            $table->dropColumn('server_id');
        });
    }
};
