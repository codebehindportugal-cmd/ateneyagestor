<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoria de endurecimento (hardening) de um servidor.
 *
 * Não confundir com `security_scans`, que já existe: essa procura sinais de
 * invasão (ficheiros PHP estranhos, SMTP a sair, rootkits). Esta olha para a
 * configuração — SSH, firewall, updates automáticos, o que o Apache mostra a
 * mais, portas abertas — e diz o que está por endurecer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hardening_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();

            $table->string('estado')->default('pendente'); // pendente|ok|avisos|falhas|erro
            $table->string('lancada_por')->nullable();     // painel|comando|agendada

            $table->unsignedSmallInteger('total')->default(0);
            $table->unsignedSmallInteger('falhas')->default(0);
            $table->unsignedSmallInteger('avisos')->default(0);

            // Uma linha por verificação: chave, label, severidade, estado,
            // detalhe, e o comando de correcção que ficou disponível.
            $table->json('resultados')->nullable();

            $table->text('erro')->nullable();
            $table->timestamp('comecou_em')->nullable();
            $table->timestamp('acabou_em')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hardening_audits');
    }
};
