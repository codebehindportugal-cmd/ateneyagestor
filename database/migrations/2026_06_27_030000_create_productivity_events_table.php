<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productivity_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_uid');
            $table->string('hostname')->nullable();
            $table->string('event_type', 30);
            $table->string('app_name')->nullable();
            $table->string('process_name')->nullable();
            $table->string('domain')->nullable();
            $table->string('activity_state', 20)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['device_uid', 'started_at']);
            $table->index(['event_type', 'started_at']);
            $table->index(['agent_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productivity_events');
    }
};
