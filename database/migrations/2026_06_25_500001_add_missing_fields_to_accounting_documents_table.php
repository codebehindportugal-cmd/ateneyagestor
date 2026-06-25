<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_documents', function (Blueprint $table) {
            $table->string('tipo', 30)->default('fatura')->after('id');           // fatura, recibo, nota_credito, outro
            $table->string('fornecedor')->nullable()->after('invoice_number');     // supplier name
            $table->string('atcud')->nullable()->after('supplier_nif');           // ATCUD code from AT QR
            $table->string('estado', 20)->default('pendente')->after('atcud');    // pendente, aprovado, pago
            $table->unsignedBigInteger('iva_cents')->default(0)->after('amount_cents'); // IVA portion in cents
        });
    }

    public function down(): void
    {
        Schema::table('accounting_documents', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'fornecedor', 'atcud', 'estado', 'iva_cents']);
        });
    }
};
