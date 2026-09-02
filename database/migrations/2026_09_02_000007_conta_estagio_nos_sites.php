<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A conta partilhada dos sites — onde entrar e como se revoga no fim.
 *
 * A password NÃO fica aqui nem em lado nenhum da base de dados: vive no gestor
 * de passwords, que foi a decisão do André. O painel guarda só o endereço e o
 * nome de utilizador.
 */
return new class extends Migration
{
    private const CONTA = 'estagio@codebehind.pt';

    public function up(): void
    {
        $now = now();

        $projectId = DB::table('projects')->where('slug', 'estagio-2026')->value('id');

        if (! $projectId) {
            return;
        }

        $notas = (string) DB::table('projects')->where('id', $projectId)->value('notes');

        $bloco = <<<TXT

        COMO ENTRAR NOS SITES
        Conta partilhada pelos três: {$this->conta()} — papel Administrador.
        A password está no gestor de passwords. Não é pedida por chat nem escrita em lado nenhum do painel; se não a tiverem, peçam ao André.
        O endereço de administração de um site é o URL do projecto + /wp-admin (ex.: https://ateneya.com/wp-admin).

        Como é uma conta partilhada, o WordPress vai atribuir tudo o que fizerem à mesma pessoa — não dá para saber depois quem alterou o quê. É por isso que o registo do que fazem tem de ficar aqui, no comentário de cada tarefa: sem isso não há memória nenhuma do trabalho.
        TXT;

        if (! str_contains($notas, 'COMO ENTRAR NOS SITES')) {
            DB::table('projects')->where('id', $projectId)->update([
                'notes'      => rtrim($notas) . "\n" . $bloco,
                'updated_at' => $now,
            ]);
        }

        $andreId = DB::table('users')->where('email', 'andre.f.mendes92@gmail.com')->value('id');
        $titulo  = 'WP-11 · Revogar os acessos no fim do estágio';

        $jaExiste = DB::table('project_tasks')
            ->where('project_id', $projectId)
            ->where('title', $titulo)
            ->exists();

        if (! $jaExiste) {
            $position = (int) DB::table('project_tasks')->where('project_id', $projectId)->max('position') + 1;

            DB::table('project_tasks')->insert([
                'project_id'       => $projectId,
                'assigned_user_id' => $andreId,
                'title'            => $titulo,
                'description'      => $this->descricaoDaRevogacao(),
                'status'           => 'pending',
                'position'         => $position,
                'created_by'       => $andreId,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }

    public function down(): void
    {
        $projectId = DB::table('projects')->where('slug', 'estagio-2026')->value('id');

        if ($projectId) {
            DB::table('project_tasks')
                ->where('project_id', $projectId)
                ->where('title', 'WP-11 · Revogar os acessos no fim do estágio')
                ->delete();
        }
    }

    private function conta(): string
    {
        return self::CONTA;
    }

    private function descricaoDaRevogacao(): string
    {
        $conta = self::CONTA;

        return <<<TXT
        Infra · Estimativa 1h — do André. Criar já, fazer no último dia.

        Uma conta partilhada com poderes de administração em sites de clientes não pode ficar viva depois de o estágio acabar. Não há como saber, pelos registos do WordPress, o que foi feito por quem, por isso a única protecção real é a conta deixar de existir no dia certo.

        O QUE FAZER (na máquina do agente, o CT 105 do Proxmox)
          cd /opt/backup-agent
          ./contas.sh --revogar --confirmar

        Isto apaga {$conta} de todos os sites WordPress e passa o conteúdo dela para outro administrador — não se perde nada do que foi publicado.

        DEPOIS
        • Marcar as contas do painel como inactivas (Equipa → cada estagiário → desligar "Conta activa"). Não apagar: o histórico do que fizeram fica.
        • Apagar a entrada do gestor de passwords.
        • Retirar os convites da organização codebehindportugal-cmd no GitHub.
        • Os sites Plesk e os que o script não alcançar ficam para tratar à mão — o `./contas.sh --listar` diz quais são.

        CRITÉRIOS DE ACEITAÇÃO
        • O `./contas.sh --listar` já não encontra a conta em nenhum site.
        • Um login de teste com essa conta falha.
        • Nenhuma conta do painel de estágio consegue entrar.
        TXT;
    }
};
