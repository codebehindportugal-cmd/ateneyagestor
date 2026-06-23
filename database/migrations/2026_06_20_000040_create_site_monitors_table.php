<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('url');
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('unknown'); // up|down|unknown
            $table->unsignedSmallInteger('last_http_code')->nullable();
            $table->unsignedInteger('last_response_ms')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('went_down_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_monitors');
    }
};
