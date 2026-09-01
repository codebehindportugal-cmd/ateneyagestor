<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\SiteUpdate;
use App\Support\Ntfy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Backs /api/agent/updates/*, chamado pelo wp_update.py no agente.
 *
 * O painel nunca liga para fora: poe o pedido em fila e fica a espera que o
 * agente venha busca-lo, como ja acontece com os backups e com o Claude.
 *
 * Segredos nao passam por aqui. O agente recebe o host, a porta, o utilizador
 * e o `agent_secret_ref`; a chave SSH e a que ele ja tem no disco.
 */
class SiteUpdateController extends Controller
{
    private function authenticatedAgent(Request $request): Agent
    {
        $user = $request->user();

        abort_unless($user instanceof Agent, 403, 'This token is not an agent token.');

        return $user;
    }

    /**
     * GET /api/agent/updates/next
     *
     * Entrega o pedido mais antigo dos servidores deste agente e marca-o a
     * correr, numa transaccao com lock: dois agentes a sondar ao mesmo tempo
     * nao podem apanhar o mesmo site.
     */
    public function next(Request $request): JsonResponse
    {
        $agent = $this->authenticatedAgent($request);

        $update = DB::transaction(function () use ($agent) {
            $candidato = SiteUpdate::query()
                ->where('status', 'queued')
                // A janela da noite vive aqui: o pedido existe desde que se
                // carrega no botao, mas so aparece ao agente dentro da janela.
                // Sem hora marcada = "agora", pedido a mao, entra sempre.
                ->where(function ($q) {
                    $q->whereNull('agendado_para');

                    if ($this->dentroDaJanela()) {
                        $q->orWhere('agendado_para', '<=', now());
                    }
                })
                ->whereHas('site.server', function ($query) use ($agent) {
                    $query->where('is_active', true)
                        ->where(fn ($q) => $q->whereNull('agent_id')->orWhere('agent_id', $agent->id));
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $candidato) {
                return null;
            }

            $candidato->update([
                'status'     => 'running',
                'started_at' => now(),
            ]);

            return $candidato;
        });

        if (! $update) {
            return response()->json(['update' => null]);
        }

        $update->load('site.server');
        $site   = $update->site;
        $server = $site->server;

        return response()->json([
            'update' => [
                'id'   => $update->id,
                'mode' => $update->mode,
                'site' => [
                    'name'       => $site->name,
                    'domain'     => $site->domain,
                    'wp_root'    => $site->wp_root,
                    'check_urls' => $site->update_check_urls ?: [],
                ],
                'server' => [
                    'name'             => $server->name,
                    'host'             => $server->host,
                    'port'             => $server->port ?: 22,
                    'user'             => $server->user ?: 'root',
                    'agent_secret_ref' => $server->agent_secret_ref ?: $server->name,
                ],
                'opcoes' => [
                    'repor_bd'             => (string) config('atualizacoes.repor_bd'),
                    'snapshot_dir'         => config('atualizacoes.snapshot_dir'),
                    'snapshot_dias'        => config('atualizacoes.snapshot_dias'),
                    'espaco_minimo_factor' => config('atualizacoes.espaco_minimo_factor'),
                    'encolhimento_maximo'  => config('atualizacoes.encolhimento_maximo'),
                ],
            ],
        ]);
    }

    /**
     * POST /api/agent/updates/{update}/progress
     *
     * Linhas de log a chegar enquanto corre, para o painel mostrar o que esta
     * a acontecer em vez de um circulo a rodar durante dez minutos.
     */
    public function progress(Request $request, SiteUpdate $update): JsonResponse
    {
        $this->authenticatedAgent($request);

        $data = $request->validate([
            'log' => ['required', 'string'],
        ]);

        $update->update([
            'log' => trim(($update->log ?? '') . "\n" . $data['log']),
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * POST /api/agent/updates/{update}/finish
     */
    public function finish(Request $request, SiteUpdate $update): JsonResponse
    {
        $this->authenticatedAgent($request);

        $data = $request->validate([
            'status'        => ['required', 'string', 'in:success,partial,failed,aborted'],
            'snapshot_path' => ['sometimes', 'string', 'nullable'],
            'antes'         => ['sometimes', 'array', 'nullable'],
            'depois'        => ['sometimes', 'array', 'nullable'],
            'itens'         => ['sometimes', 'array', 'nullable'],
            'log'           => ['sometimes', 'string', 'nullable'],
            'error'         => ['sometimes', 'string', 'nullable'],
        ]);

        // A versao vem em dois campos; junta-se aqui para o painel nao ter de
        // fazer contas dentro de um repetidor, onde e fragil.
        $itens = collect($data['itens'] ?? [])
            ->map(fn (array $item) => $item + [
                'versao' => trim(($item['de'] ?? '?') . ' -> ' . ($item['para'] ?? '?')),
            ])
            ->all();

        $update->update([
            'status'             => $data['status'],
            'snapshot_path'      => $data['snapshot_path'] ?? $update->snapshot_path,
            'antes'              => $data['antes'] ?? $update->antes,
            'depois'             => $data['depois'] ?? $update->depois,
            'itens'              => $itens,
            'total_actualizados' => collect($itens)->where('resultado', 'actualizado')->count(),
            'total_repostos'     => collect($itens)->where('resultado', 'reposto')->count(),
            'log'                => $data['log'] ?? $update->log,
            'error'              => $data['error'] ?? null,
            'finished_at'        => now(),
        ]);

        $this->avisar($update->fresh('site'));

        return response()->json(['status' => 'ok']);
    }

    /**
     * So se avisa quando ha alguma coisa a dizer: uma reposicao, uma falha, ou
     * um site que nem se chegou a tocar. Uma actualizacao que correu bem nao
     * merece um toque no telemovel — aparece no painel e no resumo diario.
     */
    private function avisar(SiteUpdate $update): void
    {
        $nome = $update->site?->name ?? 'site';
        $link = rtrim((string) config('app.url'), '/') . '/admin/site-updates';

        if ($update->status === 'success') {
            return;
        }

        if ($update->status === 'partial') {
            $repostos = collect($update->itens)
                ->where('resultado', 'reposto')
                ->pluck('slug')
                ->implode(', ');

            Ntfy::emBaixo(
                'sites',
                "Actualizacao reposta: {$nome}",
                trim("O site esta de pe, mas ficou por actualizar:\n{$repostos}"),
                $link,
            );

            return;
        }

        if ($update->status === 'aborted') {
            Ntfy::falhou(
                'sites',
                "Actualizacao nao arrancou: {$nome}",
                (string) ($update->error ?: 'Ver o painel.'),
                $link,
            );

            return;
        }

        Ntfy::emBaixo(
            'sites',
            "Actualizacao falhou: {$nome}",
            trim((string) ($update->error ?: 'Ver o log no painel.')),
            $link,
        );
    }


    /**
     * Estamos dentro da janela da noite?
     *
     * Sem isto, um pedido da noite que ficasse por apanhar — o agente em baixo
     * as 2h — arrancava as 9 da manha com o site cheio de gente. A janela tem
     * de fechar dos dois lados; um pedido que a perde espera pela noite
     * seguinte.
     */
    private function dentroDaJanela(): bool
    {
        $fuso   = (string) config('atualizacoes.fuso', 'Europe/Lisbon');
        $agora  = now()->setTimezone($fuso);
        $inicio = $agora->copy()->setTimeFromTimeString((string) config('atualizacoes.janela_inicio', '02:00'));
        $fim    = $agora->copy()->setTimeFromTimeString((string) config('atualizacoes.janela_fim', '06:00'));

        // Uma janela que atravessa a meia-noite (23:00 -> 05:00) e duas metades.
        if ($fim->lte($inicio)) {
            return $agora->gte($inicio) || $agora->lt($fim);
        }

        return $agora->between($inicio, $fim);
    }
}
