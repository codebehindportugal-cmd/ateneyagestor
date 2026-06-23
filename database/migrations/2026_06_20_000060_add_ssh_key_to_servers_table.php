<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('ssh_key_path')->nullable()->after('user')
                ->comment('Path to private key on the machine running backup-manager');
            $table->string('plesk_api_key')->nullable()->after('ssh_key_path');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['ssh_key_path', 'plesk_api_key']);
        });
    }
};
