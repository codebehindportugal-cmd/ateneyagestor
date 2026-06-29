<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_projects', function (Blueprint $table) {
            $table->boolean('sync_orders')->default(true)->after('sync_download_images');
            $table->boolean('sync_products')->default(true)->after('sync_orders');
            $table->boolean('sync_prices')->default(true)->after('sync_products');
            $table->boolean('sync_images')->default(true)->after('sync_prices');
            $table->boolean('sync_descriptions')->default(true)->after('sync_images');
            $table->boolean('sync_short_descriptions')->default(true)->after('sync_descriptions');
            $table->boolean('sync_stock')->default(true)->after('sync_short_descriptions');
            $table->boolean('sync_metadata')->default(true)->after('sync_stock');
        });
    }

    public function down(): void
    {
        Schema::table('sync_projects', function (Blueprint $table) {
            $table->dropColumn([
                'sync_orders',
                'sync_products',
                'sync_prices',
                'sync_images',
                'sync_descriptions',
                'sync_short_descriptions',
                'sync_stock',
                'sync_metadata',
            ]);
        });
    }
};
