<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A regra: "todas as segundas", "dia 5 de cada mês".
        Schema::create('routines', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('tipo')->default('tarefa');          // valores em Routine::tipos()
            $table->string('periodicidade')->default('semanal'); // Routine::periodicidades()

            // Só um destes é usado, consoante a periodicidade.
            $table->unsignedTinyInteger('dia_semana')->nullable(); // 1=segunda … 7=domingo (ISO)
            $table->unsignedTinyInteger('dia_mes')->nullable();    // 1–31, encurtado ao fim do mês
            $table->unsignedTinyInteger('mes')->nullable();        // 1–12, só para anual

            $table->unsignedInteger('amount_cents')->nullable();   // pagamentos
            $table->string('fornecedor')->nullable();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();

            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'periodicidade']);
        });

        // A materialização da regra: uma linha por semana/mês, que se marca.
        Schema::create('routine_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_id')->constrained()->cascadeOnDelete();
            $table->date('due_date');
            $table->string('status')->default('pendente'); // pendente, feito, saltado
            $table->unsignedInteger('amount_cents')->nullable(); // cópia, editável por ocorrência
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('accounting_document_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->text('notas')->nullable();
            $table->timestamps();

            // O gerador corre todos os dias: sem isto criava duplicados a cada corrida.
            $table->unique(['routine_id', 'due_date']);
            $table->index(['due_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_occurrences');
        Schema::dropIfExists('routines');
    }
};
