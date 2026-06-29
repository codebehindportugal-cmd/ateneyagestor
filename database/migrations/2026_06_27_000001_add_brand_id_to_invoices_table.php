<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices') || Schema::hasColumn('invoices', 'brand_id')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('brand_id')
                ->nullable()
                ->after('client_id')
                ->constrained('brands')
                ->nullOnDelete();
        });

        DB::table('invoices')
            ->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->whereNull('invoices.brand_id')
            ->whereNotNull('clients.brand_id')
            ->update(['invoices.brand_id' => DB::raw('clients.brand_id')]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasColumn('invoices', 'brand_id')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
        });
    }
};
