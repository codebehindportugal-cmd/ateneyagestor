<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Despesas que seguem para o painel da marca (01/10/2026).
 *
 * Uma fatura da Horta da Maria entra aqui (email, API, à mão) e, quando sai de
 * "por rever", segue para a gestao.hortadamaria.com como despesa. Ver
 * App\Services\Contabilidade\EnvioDespesaMarca.
 *
 * - brands.despesas_api_url / despesas_api_token: para onde mandar (o token
 *   cifrado com a APP_KEY, pelo cast "encrypted" do modelo).
 * - accounting_documents.viatura: a matrícula, para o custo de cada carro.
 * - accounting_documents.enviado_marca_*: quando foi, o id lá, e o último erro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('despesas_api_url')->nullable();
            $table->text('despesas_api_token')->nullable();
        });

        Schema::table('accounting_documents', function (Blueprint $table) {
            $table->string('viatura', 20)->nullable();
            $table->timestamp('enviado_marca_em')->nullable();
            $table->string('enviado_marca_ref', 100)->nullable();
            $table->text('enviado_marca_erro')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('accounting_documents', function (Blueprint $table) {
            $table->dropColumn(['viatura', 'enviado_marca_em', 'enviado_marca_ref', 'enviado_marca_erro']);
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['despesas_api_url', 'despesas_api_token']);
        });
    }
};
