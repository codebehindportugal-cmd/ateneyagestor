<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bookkeeping only -- no payment gateway integration. This just
        // tracks what's been invoiced, what's been paid, and what's
        // outstanding (per Andre's answer: "so para sabermos o que temos
        // faturado e nao e tambem recebido").
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            // Stored as integer cents to avoid float rounding issues.
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3)->default('EUR');
            $table->string('status')->default('draft'); // draft | issued | paid | overdue | cancelled
            $table->date('issued_at')->nullable();
            $table->date('due_at')->nullable();
            $table->date('paid_at')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
