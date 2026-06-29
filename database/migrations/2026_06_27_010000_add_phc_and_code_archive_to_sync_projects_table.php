<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_projects', function (Blueprint $table) {
            $table->string('phc_base_url')->nullable()->after('runner_schedule');
            $table->text('phc_api_key')->nullable()->after('phc_base_url');
            $table->string('phc_username')->nullable()->after('phc_api_key');
            $table->text('phc_password')->nullable()->after('phc_username');
            $table->string('phc_database')->nullable()->after('phc_password');
            $table->string('phc_company')->nullable()->after('phc_database');

            $table->string('code_archive_path')->nullable()->after('notes');
            $table->string('code_archive_name')->nullable()->after('code_archive_path');
        });
    }

    public function down(): void
    {
        Schema::table('sync_projects', function (Blueprint $table) {
            $table->dropColumn([
                'phc_base_url',
                'phc_api_key',
                'phc_username',
                'phc_password',
                'phc_database',
                'phc_company',
                'code_archive_path',
                'code_archive_name',
            ]);
        });
    }
};
