<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label');
            $table->string('category')->default('other'); // ssh|db|api|plesk|wordpress|email|ftp|other
            $table->string('url')->nullable();
            $table->string('username')->nullable();
            $table->text('password'); // encrypted
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
