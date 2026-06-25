<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('invoice_number')->nullable();
            $table->unsignedBigInteger('amount_cents')->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->date('date');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('category')->default('outros');
            $table->text('notes')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_documents');
    }
};
