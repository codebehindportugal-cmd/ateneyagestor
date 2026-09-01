<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_updates', function (Blueprint $table) {
            // A partir de quando o agente pode apanhar isto. E assim que a
            // janela da noite funciona: o pedido existe desde que se carrega
            // no botao, mas so fica visivel ao agente a esta hora.
            $table->timestamp('agendado_para')->nullable()->after('mode')->index();
        });
    }

    public function down(): void
    {
        Schema::table('site_updates', function (Blueprint $table) {
            $table->dropColumn('agendado_para');
        });
    }
};
