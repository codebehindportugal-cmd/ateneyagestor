<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            // Paginas extra a testar depois de cada actualizacao, alem da
            // homepage. Numa loja vale a pena por o carrinho e uma ficha de
            // produto: e onde os plugins partem, nao na entrada.
            $table->json('update_check_urls')->nullable()->after('notes');

            // Desligar o botao num site que nao se quer que seja tocado.
            $table->boolean('updates_enabled')->default(true)->after('update_check_urls');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['update_check_urls', 'updates_enabled']);
        });
    }
};
