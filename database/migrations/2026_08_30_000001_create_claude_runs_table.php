<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claude_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_task_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('queued'); // valores em ClaudeRun::statusOptions()
            $table->string('mode')->default('diagnose'); // valores em ClaudeRun::modeOptions()
            $table->longText('prompt')->nullable();      // o que foi mesmo enviado, para se poder auditar
            $table->longText('result')->nullable();
            $table->text('error')->nullable();
            $table->string('session_id')->nullable();    // permite continuar a conversa com --resume
            $table->decimal('cost_usd', 8, 4)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            // O worker procura sempre o mais antigo por fazer.
            $table->index(['status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claude_runs');
    }
};
