<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pedido de execução remota: "Correr agora" no painel marca run_requested_at;
 * o runner externo (C# no cliente) faz poll de /api/sync/should-run e limpa
 * o pedido quando chama /api/sync/runs/start.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_projects', function (Blueprint $table) {
            $table->timestamp('run_requested_at')->nullable()->after('last_run_at');
        });
    }

    public function down(): void
    {
        Schema::table('sync_projects', function (Blueprint $table) {
            $table->dropColumn('run_requested_at');
        });
    }
};
