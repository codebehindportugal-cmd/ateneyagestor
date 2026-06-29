<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounting_documents')) {
            return;
        }

        Schema::table('accounting_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('accounting_documents', 'tipo')) {
                $table->string('tipo', 30)->default('fatura')->after('id');
            }

            if (! Schema::hasColumn('accounting_documents', 'fornecedor')) {
                $table->string('fornecedor')->nullable()->after('invoice_number');
            }

            if (! Schema::hasColumn('accounting_documents', 'atcud')) {
                $after = Schema::hasColumn('accounting_documents', 'supplier_nif') ? 'supplier_nif' : 'invoice_number';
                $table->string('atcud')->nullable()->after($after);
            }

            if (! Schema::hasColumn('accounting_documents', 'estado')) {
                $table->string('estado', 20)->default('pendente')->after('atcud');
            }

            if (! Schema::hasColumn('accounting_documents', 'iva_cents')) {
                $table->unsignedBigInteger('iva_cents')->default(0)->after('amount_cents');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounting_documents')) {
            return;
        }

        Schema::table('accounting_documents', function (Blueprint $table) {
            $columns = array_filter(
                ['tipo', 'fornecedor', 'atcud', 'estado', 'iva_cents'],
                fn (string $column) => Schema::hasColumn('accounting_documents', $column)
            );

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
