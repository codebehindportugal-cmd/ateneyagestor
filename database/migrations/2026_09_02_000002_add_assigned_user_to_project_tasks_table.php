<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            // Quem é o responsável pela tarefa. Fica nulo quando ainda não foi
            // distribuída — é isso que faz uma tarefa aparecer como "por atribuir".
            $table->foreignId('assigned_user_id')
                ->nullable()
                ->after('project_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->after('completed_by')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['assigned_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropIndex(['assigned_user_id', 'status']);
            $table->dropConstrainedForeignId('assigned_user_id');
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
