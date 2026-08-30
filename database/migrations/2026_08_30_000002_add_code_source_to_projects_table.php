<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De onde e que o Claude le o codigo de um projecto.
 *
 * Nem todos tem repositorio: os Laravel vivem em C:\laragon\www, alguns
 * WordPress so existem no servidor, e ha tarefas (mudar passwords, confirmar
 * backups) que nao precisam de codigo nenhum. Em vez de um campo de caminho
 * que so serve para um dos casos, o projecto diz qual dos tres e.
 *
 * No caso "remote" o caminho nao se repete aqui: sai do Site que ja guarda
 * wp_root (WordPress) ou app_path (Laravel), e o Server ao lado ja tem host,
 * porta, utilizador e a referencia da chave.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // local | remote | none — valores em Project::codeSourceOptions()
            $table->string('code_source')->default('none')->after('url');

            // Usado quando code_source = local. Pasta no disco da maquina que
            // corre o worker; nao precisa de ser um repositorio git.
            $table->string('code_path')->nullable()->after('code_source');

            // Usado quando code_source = remote: o site de onde se tira a copia.
            $table->foreignId('site_id')->nullable()->after('code_path')
                ->constrained()->nullOnDelete();
        });

        // Versao anterior desta feature chamava-lhe repo_path.
        if (Schema::hasColumn('projects', 'repo_path')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('repo_path');
            });
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('site_id');
            $table->dropColumn(['code_source', 'code_path']);
        });
    }
};
