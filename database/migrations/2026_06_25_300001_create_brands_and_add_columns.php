<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->string('color', 7)->nullable(); // hex, e.g. #3b82f6
            $table->boolean('is_active')->default(true);
            $table->foreignId('parent_brand_id')
                ->nullable()
                ->constrained('brands')
                ->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brands')
                ->nullOnDelete()
                ->after('accountant_token');
        });

        Schema::table('accounting_documents', function (Blueprint $table) {
            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brands')
                ->nullOnDelete()
                ->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_documents', fn (Blueprint $t) => $t->dropForeignIdFor(\App\Models\Brand::class));
        Schema::table('clients',              fn (Blueprint $t) => $t->dropForeignIdFor(\App\Models\Brand::class));
        Schema::dropIfExists('brands');
    }
};
