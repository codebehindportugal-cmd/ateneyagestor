<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // phc_woo | wintouch_woo | csharp | other
            $table->string('type')->default('other');
            $table->string('site_url')->nullable();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('host')->nullable()->comment('Servidor onde o script corre');
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('unknown')->comment('unknown | ok | error');
            $table->timestamp('last_run_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_projects');
    }
};
