<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ficheiros agarrados a alguma coisa — hoje projectos e tarefas, amanha o que
 * for preciso.
 *
 * Tabela polimorfica e nao duas colunas `project_id`/`project_task_id`: a foto
 * que o cliente manda tanto pode pertencer ao projecto inteiro como a uma
 * tarefa concreta, e a lista de sitios onde isso faz sentido so' cresce. Assim
 * juntar anexos a um ticket, mais tarde, e' uma linha no modelo — nao uma
 * migracao nova de cada vez.
 *
 * O ficheiro em si vai para o NAS quando ele esta configurado, e para o disco
 * do servidor quando nao — a mesma regra dos documentos de cliente, no mesmo
 * servico. Nunca fica num URL publico: sai sempre por uma rota autenticada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();

            // attachable_type + attachable_id, com indice composto
            $table->morphs('attachable');

            $table->string('name');
            $table->string('file_path');
            $table->string('storage_type')->default('local'); // 'nas' | 'local'
            $table->string('original_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type', 100)->nullable();

            // Quem o mandou, nao quem o carregou: uma foto que o cliente enviou
            // por email e que um estagiario poe aqui e' do cliente. E' isso que
            // interessa a quem olha para a lista tres meses depois.
            $table->string('origem', 20)->default('cliente'); // 'cliente' | 'equipa'

            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
