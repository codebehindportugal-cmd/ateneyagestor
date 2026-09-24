<?php

namespace App\Console\Commands;

use App\Enums\MonitorStatus;
use App\Models\SiteMonitor;
use App\Models\SiteMonitorCheck;
use App\Support\Ntfy;
use GuzzleHttp\TransferStats;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Verifica se os sites estão de pé e quanto demoram a responder.
 *
 * Revisto a 24/09/2026 depois de validar a lista contra medições feitas no
 * browser. O que estava mal:
 *
 *  - Um site em timeout ficava "Offline" mas com o HTTP da verificação anterior
 *    (lojaamster aparecia "Offline · HTTP 200 · 15007 ms"). O código HTTP e o
 *    tempo de uma verificação falhada agora ficam a null.
 *  - O tempo de um timeout (15 s) entrava como se fosse um tempo de resposta e
 *    estragava qualquer média. Agora só se guarda tempo quando houve resposta.
 *  - O pedido saía com o User-Agent do Guzzle; alguns servidores respondem de
 *    maneira diferente a isso do que a um browser (codebehindtech.com dava 500
 *    no painel e 200 no browser). Passa a identificar-se como browser.
 *  - Uma única falha marcava o site em baixo e tocava no telemóvel. Agora uma
 *    falha é repetida uma vez, 3 s depois, antes de contar.
 *  - Mede-se também o TTFB (tempo até ao primeiro byte, somando redirects), que
 *    é o que realmente diz se o servidor/PHP está lento, e o URL final quando
 *    há redirect.
 */
class CheckSiteMonitors extends Command
{
    protected $signature = 'monitor:sites
        {--id= : Verificar só este monitor}
        {--sem-repetir : Não repetir uma falha antes de a dar como certa (usado pelo botão do painel)}';

    protected $description = 'Verifica estado HTTP e tempo de resposta dos sites monitorizados';

    private const TIMEOUT = 15;

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
        . '(KHTML, like Gecko) Chrome/128.0 Safari/537.36 AteneyaMonitor/2.0 (+https://gestao.ateneya.com)';

    public function handle(): int
    {
        $query = SiteMonitor::query()->where('is_active', true)->orderBy('id');

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $monitors = $query->get();

        if ($monitors->isEmpty()) {
            $this->info('Nenhum monitor ativo.');
            return self::SUCCESS;
        }

        $this->info("A verificar {$monitors->count()} site(s)...");

        foreach ($monitors as $monitor) {
            $this->checkMonitor($monitor);
        }

        return self::SUCCESS;
    }

    private function checkMonitor(SiteMonitor $monitor): void
    {
        $wasDown = $monitor->status === MonitorStatus::Down;

        $result = $this->probe($monitor->url);

        if (! $result['up'] && ! $this->option('sem-repetir')) {
            sleep(3);
            $result = $this->probe($monitor->url);
        }

        $status = $result['up'] ? MonitorStatus::Up : MonitorStatus::Down;

        $monitor->update([
            'status'           => $status,
            'last_http_code'   => $result['http_code'],
            'last_response_ms' => $result['total_ms'],
            'last_ttfb_ms'     => $result['ttfb_ms'],
            'last_final_url'   => $result['final_url'],
            'last_error'       => $result['error'],
            'last_checked_at'  => now(),
            'went_down_at'     => $status === MonitorStatus::Down
                ? ($wasDown ? $monitor->went_down_at : now())
                : $monitor->went_down_at,
        ]);

        SiteMonitorCheck::create([
            'site_monitor_id' => $monitor->id,
            'status'          => $status->value,
            'http_code'       => $result['http_code'],
            'response_ms'     => $result['total_ms'],
            'ttfb_ms'         => $result['ttfb_ms'],
            'error'           => $result['error'] ? mb_substr($result['error'], 0, 500) : null,
            'checked_at'      => now(),
        ]);

        $this->avisar($monitor, $wasDown, $result['up'], $result['error']);

        $icon = $result['up'] ? '✓' : '✗';
        $info = $result['http_code'] ? "HTTP {$result['http_code']}" : ($result['error'] ?? '');
        $tempo = $result['total_ms'] !== null ? " ({$result['total_ms']} ms, TTFB {$result['ttfb_ms']} ms)" : '';
        $this->line(" {$icon} {$monitor->name} — {$info}{$tempo}");
    }

    /**
     * Um pedido ao site. Os tempos vêm das estatísticas do cURL e somam todos
     * os saltos de redirect (http→https, / → /pt/...), que é o que um
     * visitante realmente espera.
     *
     * @return array{up: bool, http_code: ?int, total_ms: ?int, ttfb_ms: ?int, final_url: ?string, error: ?string}
     */
    private function probe(string $url): array
    {
        $acumulado = 0.0;
        $ttfb      = null;
        $finalUrl  = null;

        $onStats = function (TransferStats $stats) use (&$acumulado, &$ttfb, &$finalUrl) {
            $h         = $stats->getHandlerStats();
            $total     = (float) ($stats->getTransferTime() ?? ($h['total_time'] ?? 0));
            $ttfb      = $acumulado + (float) ($h['starttransfer_time'] ?? $total);
            $acumulado += $total;
            $finalUrl  = (string) $stats->getEffectiveUri();
        };

        try {
            /** @var Response $response */
            $response = Http::timeout(self::TIMEOUT)
                ->connectTimeout(10)
                ->withoutVerifying() // certificado auto-assinado não é "site em baixo"
                ->withHeaders([
                    'User-Agent'      => self::USER_AGENT,
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'pt-PT,pt;q=0.9,en;q=0.8',
                ])
                ->withOptions(['on_stats' => $onStats])
                ->get($url);

            $code = $response->status();
            $up   = $code >= 200 && $code < 400;

            return [
                'up'        => $up,
                'http_code' => $code,
                'total_ms'  => (int) round($acumulado * 1000),
                'ttfb_ms'   => $ttfb !== null ? (int) round($ttfb * 1000) : null,
                'final_url' => ($finalUrl && rtrim($finalUrl, '/') !== rtrim($url, '/')) ? $finalUrl : null,
                'error'     => $up ? null : "HTTP {$code}",
            ];
        } catch (\Throwable $e) {
            return [
                'up'        => false,
                'http_code' => null,
                'total_ms'  => null,
                'ttfb_ms'   => null,
                'final_url' => null,
                'error'     => $this->explicarErro($e->getMessage()),
            ];
        }
    }

    /** Transforma a mensagem crua do cURL em algo que se percebe no telemóvel. */
    private function explicarErro(string $msg): string
    {
        return match (true) {
            str_contains($msg, 'cURL error 28') => 'Timeout: sem resposta em ' . self::TIMEOUT . ' s',
            str_contains($msg, 'cURL error 6')  => 'DNS: o domínio não resolve',
            str_contains($msg, 'cURL error 7')  => 'Ligação recusada (servidor/porta em baixo)',
            str_contains($msg, 'cURL error 35'),
            str_contains($msg, 'cURL error 60') => 'Erro SSL: ' . mb_substr($msg, 0, 200),
            str_contains($msg, 'cURL error 47') => 'Demasiados redirects (ciclo de redirect)',
            default                             => mb_substr($msg, 0, 500),
        };
    }

    /**
     * Avisa so na transicao: quando cai e quando volta.
     *
     * Sem isto, um site em baixo verificado de 5 em 5 minutos dava 288
     * notificacoes por dia e ninguem voltava a olhar para elas.
     */
    private function avisar(SiteMonitor $monitor, bool $estavaEmBaixo, bool $estaEmCima, ?string $erro): void
    {
        // Cliente sem manutenção: continua a ser verificado, mas não toca.
        if (! $monitor->notify) {
            return;
        }

        if ($estaEmCima === $estavaEmBaixo) {
            $nome = $monitor->name;
            $url  = $monitor->url;

            if ($estaEmCima) {
                $desde = $monitor->went_down_at?->diffForHumans(null, true);

                Ntfy::recuperou(
                    'sites',
                    "Voltou: {$nome}",
                    $desde ? "{$url} está outra vez de pé. Esteve em baixo {$desde}." : "{$url} está outra vez de pé.",
                    $url,
                );

                return;
            }

            Ntfy::emBaixo('sites', "Site em baixo: {$nome}", trim("{$url}\n{$erro}"), $url);
        }
    }
}
