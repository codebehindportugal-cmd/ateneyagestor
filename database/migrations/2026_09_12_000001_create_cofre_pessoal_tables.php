<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O cofre pessoal — senhas privadas de cada pessoa, ao lado das credenciais de
 * clientes que já existem em `credentials`.
 *
 * A diferença que justifica tabelas separadas: as credenciais da casa são
 * cifradas com a APP_KEY (quem tiver o .env do servidor lê-as, e é isso que se
 * quer — são da empresa). Estas são cifradas com uma chave que só existe
 * enquanto a pessoa tem o cofre aberto, derivada de uma master password que
 * nunca chega à base de dados. Nem o administrador do painel lê o cofre de
 * outra pessoa.
 *
 * `vaults` guarda, por pessoa, a chave do cofre embrulhada (cifrada) com a
 * master password. Trocar a master password volta a embrulhar a mesma chave —
 * não é preciso mexer numa única entrada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Parâmetros do Argon2id. Ficam guardados para que se amanhã se
            // subir o custo, os cofres antigos continuem a abrir com o custo
            // com que foram criados.
            $table->string('salt');                 // base64
            $table->unsignedInteger('opslimit');
            $table->unsignedBigInteger('memlimit');

            $table->text('chave_embrulhada');       // base64(nonce+cifra) — a chave do cofre

            // Código de recuperação: o mesmo cofre embrulhado uma segunda vez,
            // com um código que se mostra uma única vez. Sem isto, perder a
            // master password é perder tudo.
            $table->string('salt_recuperacao')->nullable();
            $table->text('chave_embrulhada_recuperacao')->nullable();
            $table->timestamp('recuperacao_criada_em')->nullable();

            $table->timestamp('aberto_em')->nullable();
            $table->timestamps();
        });

        Schema::create('vault_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Em claro (para dar para procurar e ordenar): título, pasta,
            // utilizador e URL. Cifrados: a senha e as notas, que é onde está
            // o que interessa esconder.
            $table->string('pasta')->nullable();
            $table->string('titulo');
            $table->string('utilizador')->nullable();
            $table->string('url')->nullable();

            $table->text('segredo');                // base64(nonce+cifra)
            $table->text('notas')->nullable();      // base64(nonce+cifra)

            $table->timestamp('usado_em')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'pasta']);
            $table->index(['user_id', 'titulo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vault_entries');
        Schema::dropIfExists('vaults');
    }
};
