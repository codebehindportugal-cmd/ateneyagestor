<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_documents', function (Blueprint $table) {
            $table->json('products')->nullable()->after('notes');
            $table->json('image_paths')->nullable()->after('file_name');
            $table->json('image_names')->nullable()->after('image_paths');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_documents', function (Blueprint $table) {
            $table->dropColumn(['products', 'image_paths', 'image_names']);
        });
    }
};
