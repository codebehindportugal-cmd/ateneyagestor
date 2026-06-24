<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_runs', function (Blueprint $table) {
            $table->string('nas_path')->nullable()->after('finished_at');
            $table->unsignedBigInteger('size_bytes')->nullable()->after('nas_path');
            $table->unsignedSmallInteger('file_count')->default(0)->after('size_bytes');
            $table->text('log')->nullable()->after('file_count');
            $table->string('triggered_by', 30)->default('agent')->after('log'); // agent|command|filament
        });
    }

    public function down(): void
    {
        Schema::table('backup_runs', function (Blueprint $table) {
            $table->dropColumn(['nas_path', 'size_bytes', 'file_count', 'log', 'triggered_by']);
        });
    }
};
