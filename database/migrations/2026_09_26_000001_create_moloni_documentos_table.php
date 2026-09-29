<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Copia local das faturas de venda do Moloni, para os Resultados.
 *
 * E' uma copia e nao a fonte: o `moloni:sincronizar` reescreve-a. Guarda-se
 * para a pagina de Resultados abrir sem ir ao Moloni a cada clique (50
 * documentos por pedido, um ano cheio sao dezenas de pedidos).
 *
 * A marca NAO se guarda aqui: sai da serie no momento de mostrar, pelo mapa
 * em `settings` (moloni.series_marcas). Mudar o mapa muda o passado sem ter
 * de sincronizar outra vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moloni_documentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('moloni_document_id')->unique();
            $table->unsignedBigInteger('company_id');

            $table->string('tipo', 4);                 // codigo SAF-T: FT, FR, FS, NC, ND...
            $table->unsignedBigInteger('document_set_id')->nullable();
            $table->string('serie')->nullable();
            $table->unsignedInteger('numero')->nullable();

            $table->date('data');
            $table->unsignedSmallInteger('ano');
            $table->unsignedTinyInteger('mes');

            $table->string('cliente')->nullable();
            $table->string('cliente_nif', 32)->nullable();

            // Sempre positivos, como estao no Moloni. O sinal (NC subtrai)
            // aplica-se no calculo, pelo config('moloni.tipos_venda').
            $table->bigInteger('base_cents')->default(0);   // sem IVA
            $table->bigInteger('iva_cents')->default(0);
            $table->bigInteger('total_cents')->default(0);  // com IVA

            $table->unsignedTinyInteger('estado')->default(1);
            $table->timestamp('sincronizado_em')->nullable();
            $table->timestamps();

            $table->index(['ano', 'mes']);
            $table->index(['company_id', 'ano']);
            $table->index('document_set_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moloni_documentos');
    }
};
