<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_runs', function (Blueprint $table) {
            // Used by RunSyncProject command to store exit-code / error messages
            // from locally-executed sync scripts. Also exposed in the admin UI
            // to help debug runs that report 0 products/orders.
            $table->text('error')->nullable()->after('errors_count');
        });
    }

    public function down(): void
    {
        Schema::table('sync_runs', function (Blueprint $table) {
            $table->dropColumn('error');
        });
    }
};
