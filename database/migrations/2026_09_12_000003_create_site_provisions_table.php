<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada vez que se cria um site novo num VPS fica aqui o registo: que passos
 * correram, o que cada um devolveu e o que ficou por fazer.
 *
 * Serve para duas coisas: perceber onde é que parou quando alguma coisa corre
 * mal, e saber meses depois como é que aquele site foi montado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_provisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('dominio');
            $table->string('estado')->default('pendente'); // pendente|a_correr|concluido|erro

            $table->json('opcoes')->nullable();  // ssl, wordpress, email, php
            $table->json('passos')->nullable();  // um por etapa: chave, label, estado, saida

            $table->longText('log')->nullable();
            $table->text('erro')->nullable();

            $table->timestamp('comecou_em')->nullable();
            $table->timestamp('acabou_em')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_provisions');
    }
};
