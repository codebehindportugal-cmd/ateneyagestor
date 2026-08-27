<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O agente já enviava o resultado da corrida no heartbeat (backup_exit_code),
 * mas o painel validava-o e deitava-o fora — em 27/08/2026 o agente falhou 12
 * de 14 servidores por avaria do disco do NAS e nada no painel o denunciou.
 * Estas colunas guardam o veredicto da última corrida para poder ser mostrado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            // integer com sinal de propósito: subprocess.run() em Python devolve
            // returncode negativo quando o processo morre por sinal (-9 = SIGKILL,
            // tipicamente OOM). Guardar esse -9 vale mais do que rejeitar o insert.
            $table->integer('last_backup_exit_code')->nullable()->after('last_seen_at');
            $table->timestamp('last_backup_at')->nullable()->after('last_backup_exit_code');
            $table->unsignedSmallInteger('last_backup_total')->nullable()->after('last_backup_at');
            $table->unsignedSmallInteger('last_backup_failed')->nullable()->after('last_backup_total');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn([
                'last_backup_exit_code',
                'last_backup_at',
                'last_backup_total',
                'last_backup_failed',
            ]);
        });
    }
};
