<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounting_documents') || Schema::hasColumn('accounting_documents', 'supplier_nif')) {
            return;
        }

        Schema::table('accounting_documents', function (Blueprint $table) {
            $table->string('supplier_nif', 20)->nullable()->after('invoice_number');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounting_documents') || ! Schema::hasColumn('accounting_documents', 'supplier_nif')) {
            return;
        }

        Schema::table('accounting_documents', function (Blueprint $table) {
            $table->dropColumn('supplier_nif');
        });
    }
};
