<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O rasto de cada tarefa: quem a criou, quem lhe mexeu, o que mudou e
        // os comentários que a equipa deixa pelo caminho.
        Schema::create('task_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30); // valores em App\Models\TaskActivity::typeOptions()
            $table->text('body')->nullable();
            $table->json('changes')->nullable();
            $table->timestamps();

            $table->index(['project_task_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_activities');
    }
};
