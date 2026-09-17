<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avisar ou não pelo ntfy quando um site cai.
 *
 * Há clientes que não pagam a manutenção: o site continua a ser verificado e
 * aparece no painel como sempre, mas uma queda dele não é problema nosso e não
 * deve tocar no telemóvel. Por omissão avisa — um monitor novo nunca fica
 * calado sem alguém o ter decidido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_monitors', function (Blueprint $table) {
            $table->boolean('notify')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('site_monitors', function (Blueprint $table) {
            $table->dropColumn('notify');
        });
    }
};
