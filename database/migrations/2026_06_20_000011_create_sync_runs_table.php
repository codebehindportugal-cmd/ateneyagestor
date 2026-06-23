<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_project_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('success'); // success | partial | failed
            $table->unsignedInteger('products_synced')->default(0);
            $table->unsignedInteger('orders_synced')->default(0);
            $table->unsignedInteger('errors_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->longText('log')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['sync_project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};
