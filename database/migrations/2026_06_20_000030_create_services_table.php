<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('domain'); // domain|hosting|email|ssl|other
            $table->string('domain')->nullable();
            $table->string('billing_cycle')->default('annual'); // monthly|annual|biennial
            $table->unsignedInteger('amount_cents')->default(0);
            $table->date('renewal_date');
            $table->boolean('auto_renew')->default(true);
            $table->string('registrar')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['renewal_date', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
