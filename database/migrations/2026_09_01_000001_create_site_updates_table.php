<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_updates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();

            // queued -> running -> success | partial | failed | aborted
            // partial = actualizou o que deu e repos o que partiu.
            // aborted = nem chegou a comecar (site ja estava em baixo, sem
            // espaco em disco, sem wp-cli) — nao se mexeu em nada.
            $table->string('status')->default('queued')->index();

            // 'apply' actualiza; 'dry_run' so lista o que ha para actualizar.
            $table->string('mode')->default('apply');

            // Pasta no PROPRIO servidor com o tar e o dump de antes. Fica la
            // uns dias: reposicao a partir do NAS demorava demasiado, e a
            // graca disto e voltar atras em segundos.
            $table->string('snapshot_path')->nullable();

            // Estado do site antes e depois: versao do WP, codigo HTTP,
            // tamanho da pagina, plugins activos. E a comparacao destes dois
            // que decide se a actualizacao correu bem.
            $table->json('antes')->nullable();
            $table->json('depois')->nullable();

            // Uma entrada por plugin/tema/core: slug, de, para, resultado.
            $table->json('itens')->nullable();

            $table->unsignedSmallInteger('total_actualizados')->default(0);
            $table->unsignedSmallInteger('total_repostos')->default(0);

            $table->longText('log')->nullable();
            $table->text('error')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_updates');
    }
};
