<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('running');      // running, clean, warning, critical, failed
            $table->string('triggered_by')->default('command'); // command, schedule, filament
            $table->integer('findings_count')->default(0);
            $table->json('findings')->nullable();              // array of check result objects
            $table->text('log')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_scans');
    }
};
