<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Duas coisas de uma vez, porque nascem do mesmo pedido:
 *
 * 1. O contabilista precisa de dizer, documento a documento, se ja o meteu no
 *    software dele. Sem isto a unica forma de saber era perguntar-lhe.
 * 2. As facturas passam a entrar sozinhas pelo email faturacao@ateneya.com, e
 *    e' preciso saber de que mensagem veio cada uma — e nao criar a mesma duas
 *    vezes quando o fornecedor reenvia o mesmo anexo.
 *
 * O `ficheiro_hash` e' o que trava os duplicados: e' o sha256 do anexo, por isso
 * o mesmo PDF reenviado uma semana depois nao volta a entrar, mesmo que o
 * assunto ou o UID da mensagem tenham mudado.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounting_documents')) {
            return;
        }

        Schema::table('accounting_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('accounting_documents', 'importado_contabilidade')) {
                $table->boolean('importado_contabilidade')->default(false)->index()->after('estado');
            }

            if (! Schema::hasColumn('accounting_documents', 'importado_em')) {
                $table->timestamp('importado_em')->nullable()->after('importado_contabilidade');
            }

            if (! Schema::hasColumn('accounting_documents', 'importado_nota')) {
                $table->string('importado_nota')->nullable()->after('importado_em');
            }

            if (! Schema::hasColumn('accounting_documents', 'origem')) {
                $table->string('origem', 20)->default('manual')->index()->after('importado_nota');
            }

            if (! Schema::hasColumn('accounting_documents', 'email_message_id')) {
                $table->string('email_message_id', 255)->nullable()->index()->after('origem');
            }

            if (! Schema::hasColumn('accounting_documents', 'email_de')) {
                $table->string('email_de')->nullable()->after('email_message_id');
            }

            if (! Schema::hasColumn('accounting_documents', 'email_assunto')) {
                $table->string('email_assunto')->nullable()->after('email_de');
            }

            if (! Schema::hasColumn('accounting_documents', 'email_recebido_em')) {
                $table->timestamp('email_recebido_em')->nullable()->after('email_assunto');
            }

            if (! Schema::hasColumn('accounting_documents', 'ficheiro_hash')) {
                // Unico, mas anulavel: os documentos que ja la estao ficam a
                // null e o MySQL aceita varios nulls num indice unico.
                $table->string('ficheiro_hash', 64)->nullable()->unique()->after('email_recebido_em');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('accounting_documents')) {
            return;
        }

        Schema::table('accounting_documents', function (Blueprint $table) {
            foreach ([
                'importado_contabilidade',
                'importado_em',
                'importado_nota',
                'origem',
                'email_message_id',
                'email_de',
                'email_assunto',
                'email_recebido_em',
                'ficheiro_hash',
            ] as $coluna) {
                if (Schema::hasColumn('accounting_documents', $coluna)) {
                    $table->dropColumn($coluna);
                }
            }
        });
    }
};
