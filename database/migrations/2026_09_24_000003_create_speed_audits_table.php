<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoria de velocidade de um servidor e dos sites WordPress que tem.
 *
 * A mesma forma da `hardening_audits` (uma linha por auditoria, resultados em
 * JSON), mais o grupo em cada resultado — "Máquina" ou o domínio do site.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speed_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();

            $table->string('estado')->default('pendente'); // pendente|ok|avisos|falhas|erro
            $table->string('lancada_por')->nullable();

            $table->unsignedSmallInteger('total')->default(0);
            $table->unsignedSmallInteger('falhas')->default(0);
            $table->unsignedSmallInteger('avisos')->default(0);

            $table->json('resultados')->nullable();

            $table->text('erro')->nullable();
            $table->timestamp('comecou_em')->nullable();
            $table->timestamp('acabou_em')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speed_audits');
    }
};
