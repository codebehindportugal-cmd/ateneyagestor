<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('sync_projects', 'runner_mode')) {
            return;
        }

        Schema::table('sync_projects', function (Blueprint $table) {
            $table->string('runner_mode', 20)
                ->default('external')
                ->after('host')
                ->comment('local | external');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('sync_projects', 'runner_mode')) {
            return;
        }

        Schema::table('sync_projects', function (Blueprint $table) {
            $table->dropColumn('runner_mode');
        });
    }
};
