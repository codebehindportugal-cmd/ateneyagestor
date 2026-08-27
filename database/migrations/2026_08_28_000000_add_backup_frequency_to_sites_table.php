<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Até aqui o agente copiava todos os sites todas as noites e a retenção só
 * sabia apagar por idade — `keep_min_copies` era um mínimo, nunca um limite.
 * Não havia forma de dizer "este é mensal" nem "guarda só 2".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('backup_frequency', 10)->default('daily')->after('is_active');
            $table->unsignedSmallInteger('retention_max_copies')->nullable()->after('retention_keep_min_copies');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['backup_frequency', 'retention_max_copies']);
        });
    }
};
