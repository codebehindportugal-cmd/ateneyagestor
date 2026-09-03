<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Regista a avaria da máquina de casa (Proxmox / CT 105) como tarefa.
 *
 * Fica aqui e não escrita à mão no painel para não se perder: é a avaria que
 * deixou 24 sites sem backup nenhum, e a data em que começou tem de estar
 * escrita em sítio nenhum que dependa de alguém se lembrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $projectId = DB::table('projects')->where('slug', 'backup-manager')->value('id');
        $andreId   = DB::table('users')->where('email', 'andre.f.mendes92@gmail.com')->value('id');

        if (! $projectId) {
            return;
        }

        $titulo = 'Máquina dos backups em baixo desde 29/08 — não liga';

        if (DB::table('project_tasks')->where('project_id', $projectId)->where('title', $titulo)->exists()) {
            return;
        }

        $position = (int) DB::table('project_tasks')->where('project_id', $projectId)->max('position') + 1;

        DB::table('project_tasks')->insert([
            'project_id'       => $projectId,
            'assigned_user_id' => $andreId,
            'title'            => $titulo,
            'description'      => $this->descricao(),
            'status'           => 'in_progress',
            'position'         => $position,
            'estimated_hours'  => 2,
            'created_by'       => $andreId,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Máquina dos backups em baixo desde 29/08 — não liga')
            ->delete();
    }

    private function descricao(): string
    {
        return <<<TXT
        Infra · URGENTE — 24 sites sem cópia de segurança.

        O QUE SE SABE
        • Último contacto do agente "Backup Proxmox (casa)": 29/08/2026 18:18.
        • Última corrida de backups: 29/08, 24 de 24 com sucesso. Depois disso, nada.
        • A 02/09 a máquina não dá sinal nenhum ao ligar à corrente — nem LED. Suspeita: transformador queimado.
        • Consequência: as cópias mais recentes no NAS são de 29/08. Tudo o que mudou nos sites desde então não tem cópia em lado nenhum.

        POR ESTA ORDEM
        1. Ler a etiqueta do transformador (tensão, amperagem, polaridade) e testar com outro igual ou de amperagem superior. É a causa mais provável e a mais barata.
        2. Se com outra fonte continuar sem dar sinal, é a placa. Nesse caso o que interessa é tirar os discos e montá-los noutra máquina — uma fonte que morre raramente leva os discos com ela.
        3. Confirmar o estado do disco Toshiba assim que arrancar (o My Book de 2 TB já estava em falha e foi desmontado — ver a tarefa "Migrar os backups para disco saudável").
        4. Quando voltar, confirmar no painel que o agente volta a "Online" e forçar uma corrida de backups em vez de esperar pelas 03:00.

        A REBOQUE DESTA AVARIA
        • A chave SSH que abre todas as VPS vive no disco desta máquina. Enquanto ela estiver em baixo, o PC do André só entra no servidor de Desenvolvimento — é o que trava a criação das contas de estágio nos sites.

        JÁ CORRIGIDO
        • O resumo diário do ntfy dizia "tudo de pé" todas as manhãs durante os quatro dias, porque olhava para backups que FALHARAM e nunca para backups que deixaram de acontecer. Passa a avisar quando um agente fica sem dar sinal, e o resumo diário passa a incluir isso.
        TXT;
    }
};
