<?php

namespace App\Services\Velocidade;

use App\Models\Server;
use App\Models\Site;

/**
 * O que se verifica para um site ficar rápido, e como se corrige.
 *
 * Nasceu a 24/09/2026: a monitorização mostrou sites com médias de 2 a 6 s e o
 * TTFB era ~90% desse tempo — ou seja, o tempo perde-se no servidor a gerar a
 * página (PHP + WordPress + MySQL), não na rede nem nas imagens.
 *
 * Duas camadas:
 *  - A máquina: OPcache, versão/modo do PHP, Redis, compressão, HTTP/2, cache
 *    do browser, buffer do MySQL, recursos.
 *  - Cada site WordPress activo nessa máquina: TTFB medido na própria máquina
 *    (sem rede pelo meio), cache de página, object cache, wp-cron, opções em
 *    autoload, número de plugins.
 *
 * Como no Endurecimento: as verificações só lêem; as correcções estão à vista
 * e só correm com o botão. Nos servidores com Plesk ficam de fora as correcções
 * de Apache/PHP da máquina, porque essa configuração é do Plesk.
 *
 * Os scripts são nowdoc com marcadores {{...}} substituídos por valores já
 * escapados — assim os $ do shell não se misturam com os do PHP.
 */
class CatalogoVelocidade
{
    private const GRUPO_MAQUINA = 'Máquina';

    /** Plugins de cache de página que conhecemos. */
    private const PLUGINS_CACHE = [
        'cache-enabler', 'wp-super-cache', 'w3-total-cache', 'wp-fastest-cache', 'litespeed-cache',
        'wp-rocket', 'breeze', 'sg-cachepress', 'hummingbird-performance', 'comet-cache',
        'wp-optimize', 'swift-performance-lite', 'nitropack', 'flying-press', 'autoptimize-cache',
    ];

    /** @return array<string, VerificacaoVelocidade> */
    public static function para(Server $server): array
    {
        $verificacoes = self::daMaquina($server);

        foreach (self::sitesWordPress($server) as $site) {
            foreach (self::doSite($site) as $v) {
                $verificacoes[$v->chave] = $v;
            }
        }

        if ($server->hasPlesk()) {
            $verificacoes = array_filter($verificacoes, fn (VerificacaoVelocidade $v) => $v->aplicavelComPlesk);
        }

        return $verificacoes;
    }

    public static function verificacao(Server $server, string $chave): ?VerificacaoVelocidade
    {
        return self::para($server)[$chave] ?? null;
    }

    /** @return \Illuminate\Support\Collection<int, Site> */
    public static function sitesWordPress(Server $server)
    {
        return $server->activeSites()
            ->orderBy('domain')
            ->get()
            ->filter(function (Site $s) {
                $tipo = $s->type instanceof \BackedEnum ? $s->type->value : $s->type;

                return $tipo === 'wordpress' && filled($s->domain) && ! str_ends_with($s->domain, '.plesk.page');
            })
            ->values();
    }

    // ------------------------------------------------------------------
    // Máquina
    // ------------------------------------------------------------------

    /** @return array<string, VerificacaoVelocidade> */
    private static function daMaquina(Server $server): array
    {
        $g = self::GRUPO_MAQUINA;
        $lista = [];

        $lista[] = new VerificacaoVelocidade(
            grupo: $g,
            chave: 'recursos',
            label: 'CPU, memória e carga',
            severidade: 'importante',
            porque: 'Se a máquina está sem memória (a usar swap) ou com a carga acima dos processadores, nenhuma optimização de site compensa.',
            comando: <<<'SH'
            echo "cpus=$(nproc)"
            echo "load=$(cut -d' ' -f1-3 /proc/loadavg)"
            free -m | awk '/^Mem:/ {print "ram_total="$2; print "ram_disp="$7} /^Swap:/ {print "swap_total="$2; print "swap_usado="$3}'
            SH,
            avaliar: function (string $s): array {
                $v = self::chaves($s);
                $cpus = max(1, (int) ($v['cpus'] ?? 1));
                $load = (float) explode(' ', $v['load'] ?? '0')[0];
                $ram = (int) ($v['ram_total'] ?? 0);
                $disp = (int) ($v['ram_disp'] ?? 0);
                $swapT = (int) ($v['swap_total'] ?? 0);
                $swapU = (int) ($v['swap_usado'] ?? 0);

                $detalhe = "{$cpus} CPU · carga " . ($v['load'] ?? '?') . ' · RAM ' . round($ram / 1024, 1) . ' GB, livre ' . round($disp / 1024, 1)
                    . ' GB · swap ' . ($swapT ? "{$swapU}/{$swapT} MB" : 'sem swap');

                $problemas = [];
                if ($load > $cpus) {
                    $problemas[] = 'carga acima do número de CPUs';
                }
                if ($ram && $disp < $ram * 0.10) {
                    $problemas[] = 'menos de 10% de RAM disponível';
                }
                if ($swapT && $swapU > $swapT * 0.25) {
                    $problemas[] = 'a usar swap a sério';
                }

                return $problemas
                    ? ['estado' => 'falha', 'detalhe' => implode(', ', $problemas) . "\n{$detalhe}\nÉ preciso mais RAM/CPU ou menos sites nesta máquina."]
                    : ['estado' => 'ok', 'detalhe' => $detalhe];
            },
        );

        $lista[] = new VerificacaoVelocidade(
            grupo: $g,
            chave: 'php_modo',
            label: 'Versão e modo do PHP',
            severidade: 'importante',
            porque: 'PHP 8.2+ com PHP-FPM é bastante mais rápido que versões antigas ou mod_php (que também impede HTTP/2).',
            comando: <<<'SH'
            systemctl list-units --type=service --state=running --no-legend 'php*-fpm*' 2>/dev/null | awk '{print "fpm " $1}'
            apache2ctl -M 2>/dev/null | grep -iE 'php[0-9_]*_module' | awk '{print "modphp " $1}'
            apache2ctl -V 2>/dev/null | awk -F': ' '/Server MPM/ {print "mpm " $2}'
            SH,
            avaliar: function (string $s): array {
                preg_match_all('/php(\d)\.(\d+)/', $s, $m, PREG_SET_ORDER);
                $versoes = array_unique(array_map(fn ($x) => (int) $x[1] * 100 + (int) $x[2], $m));
                $modPhp = str_contains($s, 'modphp');
                $texto = trim($s) ?: 'não deu para ler';

                if ($modPhp) {
                    return ['estado' => 'falha', 'detalhe' => "O Apache corre o PHP como módulo (mod_php + prefork): mais lento e sem HTTP/2. Passar para PHP-FPM é trabalho à mão, site a site.\n{$texto}"];
                }
                if ($versoes && min($versoes) < 801) {
                    return ['estado' => 'falha', 'detalhe' => "Há PHP abaixo de 8.1 (sem actualizações de segurança). Actualizar à mão, depois de testar os plugins.\n{$texto}"];
                }
                if ($versoes && min($versoes) < 802) {
                    return ['estado' => 'aviso', 'detalhe' => "PHP 8.2 ou 8.3 dá mais uns 5–10%.\n{$texto}"];
                }

                return ['estado' => 'ok', 'detalhe' => $texto];
            },
            aplicavelComPlesk: false,
        );

        $lista[] = new VerificacaoVelocidade(
            grupo: $g,
            chave: 'opcache',
            label: 'OPcache do PHP',
            severidade: 'critica',
            porque: 'Sem OPcache (ou com pouca memória) o PHP recompila o WordPress inteiro em cada visita. É o ganho mais barato que existe.',
            comando: <<<'SH'
            for d in /etc/php/*/fpm /etc/php/*/apache2; do
              [ -d "$d" ] || continue
              if ls "$d"/conf.d/*opcache* >/dev/null 2>&1; then carregado=sim; else carregado=nao; fi
              vals=$(cat "$d"/php.ini "$d"/conf.d/*.ini 2>/dev/null | grep -E '^[[:space:]]*opcache\.(enable|memory_consumption|max_accelerated_files|interned_strings_buffer)[[:space:]]*=' | tr -d ' ' | awk -F= '{m[$1]=$2} END {for (k in m) printf "%s=%s ", k, m[k]}')
              echo "$d carregado=$carregado $vals"
            done
            SH,
            avaliar: function (string $s): array {
                $s = trim($s);
                if ($s === '') {
                    return ['estado' => 'aviso', 'detalhe' => 'Não encontrei configurações de PHP em /etc/php (máquina com outro layout?).'];
                }

                $mal = [];
                $fraco = [];
                foreach (explode("\n", $s) as $linha) {
                    if (str_contains($linha, 'carregado=nao') || preg_match('/opcache\.enable=(0|off|false)\b/i', $linha)) {
                        $mal[] = $linha;
                        continue;
                    }
                    preg_match('/opcache\.memory_consumption=(\d+)/', $linha, $mem);
                    preg_match('/opcache\.max_accelerated_files=(\d+)/', $linha, $fic);
                    if ((int) ($mem[1] ?? 128) < 192 || (int) ($fic[1] ?? 10000) < 16000) {
                        $fraco[] = $linha;
                    }
                }

                return match (true) {
                    $mal !== []   => ['estado' => 'falha', 'detalhe' => "OPcache desligado:\n" . implode("\n", $mal)],
                    $fraco !== [] => ['estado' => 'aviso', 'detalhe' => "OPcache ligado mas com valores por omissão (128 MB), curtos para vários WordPress:\n" . implode("\n", $fraco)],
                    default       => ['estado' => 'ok', 'detalhe' => $s],
                };
            },
            correcao: <<<'SH'
            for d in /etc/php/*/fpm /etc/php/*/apache2; do
              [ -d "$d/conf.d" ] || continue
              cat > "$d/conf.d/99-ateneya-velocidade.ini" <<'INI'
            ; Escrito pelo painel gestao.ateneya.com (Velocidade). Apagar este ficheiro repõe o que estava.
            opcache.enable=1
            opcache.memory_consumption=256
            opcache.interned_strings_buffer=32
            opcache.max_accelerated_files=30000
            opcache.validate_timestamps=1
            opcache.revalidate_freq=60
            realpath_cache_size=4096K
            realpath_cache_ttl=600
            INI
              echo "escrito $d/conf.d/99-ateneya-velocidade.ini"
            done
            for s in $(systemctl list-units --type=service --state=running --no-legend 'php*-fpm*' | awk '{print $1}'); do systemctl reload "$s" && echo "recarregado $s"; done
            if apache2ctl -M 2>/dev/null | grep -qi 'php[0-9_]*_module'; then systemctl reload apache2 && echo 'recarregado apache2'; fi
            SH,
            perigo: 'Recarrega o PHP-FPM (não reinicia: os pedidos em curso acabam normalmente). Um ficheiro alterado por FTP pode demorar até 60 s a aparecer.',
            aplicavelComPlesk: false,
        );

        $lista[] = new VerificacaoVelocidade(
            grupo: $g,
            chave: 'redis',
            label: 'Redis (para object cache)',
            severidade: 'importante',
            porque: 'Com Redis o WordPress guarda em memória o resultado das consultas à base de dados em vez de as repetir em cada página.',
            comando: <<<'SH'
            echo "servico=$(systemctl is-active redis-server 2>/dev/null || echo inactivo)"
            for d in /etc/php/*/fpm; do [ -d "$d" ] || continue; if ls "$d"/conf.d/*redis* >/dev/null 2>&1; then echo "ext $d sim"; else echo "ext $d nao"; fi; done
            SH,
            avaliar: function (string $s): array {
                $activo = str_contains($s, 'servico=active');
                $semExt = substr_count($s, ' nao');

                return match (true) {
                    ! $activo    => ['estado' => 'aviso', 'detalhe' => "Redis não está instalado/a correr.\n" . trim($s)],
                    $semExt > 0  => ['estado' => 'aviso', 'detalhe' => "Redis a correr, mas falta a extensão php-redis em alguma versão de PHP.\n" . trim($s)],
                    default      => ['estado' => 'ok', 'detalhe' => trim($s)],
                };
            },
            correcao: <<<'SH'
            export DEBIAN_FRONTEND=noninteractive
            pacotes="redis-server"
            for d in /etc/php/*/fpm; do [ -d "$d" ] || continue; v=$(basename "$(dirname "$d")"); pacotes="$pacotes php$v-redis"; done
            apt-get update -qq && apt-get install -y $pacotes || exit 1
            grep -qE '^maxmemory ' /etc/redis/redis.conf || printf '\n# Ateneya: limite para nao comer a RAM dos sites\nmaxmemory 256mb\nmaxmemory-policy allkeys-lru\n' >> /etc/redis/redis.conf
            systemctl enable --now redis-server && systemctl restart redis-server
            for s in $(systemctl list-units --type=service --state=running --no-legend 'php*-fpm*' | awk '{print $1}'); do systemctl reload "$s"; done
            systemctl is-active redis-server
            SH,
            perigo: 'Instala pacotes (redis-server e php-redis) e recarrega o PHP-FPM. O Redis fica limitado a 256 MB e só escuta em localhost. Depois é preciso ligar o object cache em cada site.',
            aplicavelComPlesk: false,
        );

        $lista[] = new VerificacaoVelocidade(
            grupo: $g,
            chave: 'compressao',
            label: 'Compressão (gzip/brotli)',
            severidade: 'importante',
            porque: 'HTML, CSS e JS comprimidos ficam 70–80% mais pequenos: a página chega mais depressa, sobretudo em rede móvel.',
            comando: <<<'SH'
            if command -v apache2ctl >/dev/null 2>&1; then
              echo 'web=apache'
              apache2ctl -M 2>/dev/null | grep -oE '(deflate|brotli)_module' | sort -u
            elif command -v nginx >/dev/null 2>&1; then
              echo 'web=nginx'
              nginx -T 2>/dev/null | grep -vE '^[[:space:]]*#' | grep -oE '^[[:space:]]*(gzip|gzip_types|brotli)[[:space:]][^;]*' | sed 's/^[[:space:]]*//' | sort -u | head -6
            else
              echo 'web=desconhecido'
            fi
            SH,
            avaliar: function (string $s): array {
                $det = trim(preg_replace('/^web=.*$/m', '', $s));
                if (str_contains($s, 'web=apache')) {
                    return $det === ''
                        ? ['estado' => 'falha', 'detalhe' => 'Apache sem mod_deflate nem mod_brotli.']
                        : ['estado' => 'ok', 'detalhe' => $det];
                }
                if (str_contains($s, 'web=nginx')) {
                    $ligado = (bool) preg_match('/^gzip\s+on/m', $det) || str_contains($det, 'brotli on');
                    $tipos = str_contains($det, 'text/css') && str_contains($det, 'javascript');

                    return match (true) {
                        ! $ligado => ['estado' => 'falha', 'detalhe' => "nginx sem gzip.\n{$det}"],
                        ! $tipos  => ['estado' => 'falha', 'detalhe' => "nginx com gzip, mas só para HTML (falta gzip_types para CSS/JS).\n{$det}"],
                        default   => ['estado' => 'ok', 'detalhe' => $det],
                    };
                }

                return ['estado' => 'aviso', 'detalhe' => 'Não encontrei Apache nem nginx.'];
            },
            correcao: <<<'SH'
            if command -v apache2ctl >/dev/null 2>&1; then
              a2enmod -q deflate
              a2enmod -q brotli 2>/dev/null || echo 'brotli nao disponivel (fica o gzip)'
              apache2ctl configtest && systemctl reload apache2 && apache2ctl -M 2>/dev/null | grep -E 'deflate|brotli'
            elif command -v nginx >/dev/null 2>&1; then
              F=/etc/nginx/conf.d/zz-ateneya-gzip.conf
              rm -f "$F"
              # Só se escreve o que ainda não está no contexto http (repetir uma directiva parte o nginx).
              ACTUAL=$(cat /etc/nginx/nginx.conf /etc/nginx/conf.d/*.conf 2>/dev/null | grep -vE '^[[:space:]]*#')
              {
                echo '# Escrito pelo painel gestao.ateneya.com (Velocidade). Apagar e recarregar o nginx desfaz.'
                for par in 'gzip on' 'gzip_vary on' 'gzip_proxied any' 'gzip_comp_level 5' 'gzip_min_length 256' \
                  'gzip_types text/plain text/css text/xml text/javascript application/javascript application/x-javascript application/json application/xml application/rss+xml application/ld+json image/svg+xml font/ttf font/otf application/vnd.ms-fontobject'; do
                  d=${par%% *}
                  printf '%s\n' "$ACTUAL" | grep -qE "^[[:space:]]*$d[[:space:]]" || echo "$par;"
                done
              } > "$F"
              cat "$F"
              if nginx -t 2>&1; then systemctl reload nginx && echo 'gzip ligado para CSS/JS/JSON/SVG'; else rm -f "$F"; echo 'nginx -t falhou: desfeito'; exit 1; fi
            else
              echo 'Não encontrei Apache nem nginx.'; exit 1
            fi
            SH,
            aplicavelComPlesk: false,
        );

        $lista[] = new VerificacaoVelocidade(
            grupo: $g,
            chave: 'http2',
            label: 'HTTP/2',
            severidade: 'info',
            porque: 'Com HTTP/2 o browser pede CSS, JS e imagens todos pela mesma ligação, em paralelo.',
            comando: <<<'SH'
            if command -v apache2ctl >/dev/null 2>&1; then
              echo 'web=apache'
              apache2ctl -M 2>/dev/null | grep -oE '(http2|mpm_[a-z]+)_module' | sort -u
              grep -rhiE '^[[:space:]]*Protocols' /etc/apache2/apache2.conf /etc/apache2/conf-enabled /etc/apache2/sites-enabled 2>/dev/null | sort -u | head -3
            elif command -v nginx >/dev/null 2>&1; then
              echo 'web=nginx'
              echo "versao=$(nginx -v 2>&1 | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')"
              echo "com_h2=$(nginx -T 2>/dev/null | grep -vE '^[[:space:]]*#' | grep -cE 'listen[^;]*443[^;]*http2|^[[:space:]]*http2[[:space:]]+on')"
              echo "listen_ssl=$(nginx -T 2>/dev/null | grep -vE '^[[:space:]]*#' | grep -cE 'listen[^;]*443')"
            else
              echo 'web=desconhecido'
            fi
            SH,
            avaliar: function (string $s): array {
                if (str_contains($s, 'web=nginx')) {
                    $v = self::chaves($s);

                    return (int) ($v['com_h2'] ?? 0) > 0
                        ? ['estado' => 'ok', 'detalhe' => "nginx {$v['versao']} com HTTP/2"]
                        : ['estado' => 'aviso', 'detalhe' => 'nginx ' . ($v['versao'] ?? '?') . ' sem HTTP/2 (' . ($v['listen_ssl'] ?? 0) . ' listen 443)'];
                }
                if (str_contains($s, 'mpm_prefork')) {
                    return ['estado' => 'aviso', 'detalhe' => "Apache em prefork (por causa do mod_php): o HTTP/2 não funciona assim. Primeiro passar para PHP-FPM.\n" . trim($s)];
                }
                if (str_contains($s, 'http2_module') && stripos($s, 'h2') !== false) {
                    return ['estado' => 'ok', 'detalhe' => trim($s)];
                }

                return ['estado' => 'aviso', 'detalhe' => trim($s) ?: 'sem HTTP/2'];
            },
            correcao: <<<'SH'
            if command -v apache2ctl >/dev/null 2>&1; then
              if apache2ctl -M 2>/dev/null | grep -q mpm_prefork; then echo 'Apache em prefork: passa primeiro para PHP-FPM + mpm_event.'; exit 1; fi
              a2enmod -q http2
              printf '# Escrito pelo painel gestao.ateneya.com (Velocidade)\nProtocols h2 http/1.1\n' > /etc/apache2/conf-available/zz-ateneya-http2.conf
              a2enconf -q zz-ateneya-http2
              if apache2ctl configtest; then systemctl reload apache2 && echo 'HTTP/2 ligado'; else a2disconf -q zz-ateneya-http2; echo 'configtest falhou: desfeito'; exit 1; fi
            elif command -v nginx >/dev/null 2>&1; then
              v=$(nginx -v 2>&1 | grep -oE '[0-9]+\.[0-9]+\.[0-9]+')
              if printf '1.25.1\n%s\n' "$v" | sort -V -C; then
                # nginx 1.25.1+: uma linha no contexto http liga para todos os sites.
                printf '# Escrito pelo painel gestao.ateneya.com (Velocidade)\nhttp2 on;\n' > /etc/nginx/conf.d/zz-ateneya-http2.conf
                if nginx -t 2>&1; then systemctl reload nginx && echo "HTTP/2 ligado (nginx $v)"; else rm -f /etc/nginx/conf.d/zz-ateneya-http2.conf; echo 'nginx -t falhou: desfeito'; exit 1; fi
              else
                # nginx antigo: acrescenta http2 a cada "listen ... 443 ... ssl", com cópia antes.
                B=/root/ateneya-backup-nginx-$(date +%Y%m%d%H%M%S); mkdir -p "$B"
                for f in /etc/nginx/sites-enabled/* /etc/nginx/conf.d/*.conf; do
                  [ -f "$f" ] || continue
                  grep -qE 'listen[^;]*443[^;]*ssl' "$f" || continue
                  real=$(readlink -f "$f"); cp -a "$real" "$B/"
                  sed -i -E '/http2/!s/(listen[^;]*443[^;]*ssl)([^;]*);/\1 http2\2;/' "$real"
                  echo "alterado $real"
                done
                if nginx -t 2>&1; then systemctl reload nginx && echo "HTTP/2 ligado (nginx $v). Cópia em $B"; else cp -a "$B"/* /etc/nginx/sites-available/ 2>/dev/null; echo "nginx -t falhou. Cópia dos ficheiros em $B — confirma à mão"; exit 1; fi
              fi
            else
              echo 'Não encontrei Apache nem nginx.'; exit 1
            fi
            SH,
            aplicavelComPlesk: false,
        );

        $lista[] = new VerificacaoVelocidade(
            grupo: $g,
            chave: 'cache_browser',
            label: 'Cache do browser para imagens, CSS e JS',
            severidade: 'importante',
            porque: 'Sem cabeçalhos de validade, o visitante volta a descarregar as mesmas imagens e ficheiros em cada página.',
            comando: <<<'SH'
            if command -v apache2ctl >/dev/null 2>&1; then
              echo 'web=apache'
              apache2ctl -M 2>/dev/null | grep -oE '(expires|headers)_module' | sort -u
              [ -e /etc/apache2/conf-enabled/zz-ateneya-cache.conf ] && echo 'conf=sim' || echo 'conf=nao'
            elif command -v nginx >/dev/null 2>&1; then
              echo 'web=nginx'
              [ -e /etc/nginx/conf.d/zz-ateneya-cache.conf ] && echo 'conf=sim' || echo 'conf=nao'
              echo "expires_nos_sites=$(nginx -T 2>/dev/null | grep -vE '^[[:space:]]*#' | grep -cE '^[[:space:]]*expires[[:space:]]')"
            else
              echo 'web=desconhecido'
            fi
            SH,
            avaliar: function (string $s): array {
                if (str_contains($s, 'conf=sim')) {
                    return ['estado' => 'ok', 'detalhe' => 'regras de validade para imagens, fontes, CSS e JS'];
                }
                if (str_contains($s, 'web=nginx')) {
                    $n = (int) (self::chaves($s)['expires_nos_sites'] ?? 0);

                    return $n > 0
                        ? ['estado' => 'aviso', 'detalhe' => "Há {$n} regra(s) expires em alguns sites, mas não uma geral."]
                        : ['estado' => 'falha', 'detalhe' => 'nginx sem cabeçalhos de validade para ficheiros estáticos.'];
                }

                return ['estado' => 'falha', 'detalhe' => trim($s)];
            },
            correcao: <<<'SH'
            if command -v apache2ctl >/dev/null 2>&1; then
              a2enmod -q expires headers
              cat > /etc/apache2/conf-available/zz-ateneya-cache.conf <<'CONF'
            # Escrito pelo painel gestao.ateneya.com (Velocidade). a2disconf zz-ateneya-cache para desligar.
            <IfModule mod_expires.c>
                ExpiresActive On
                ExpiresByType image/jpeg "access plus 1 year"
                ExpiresByType image/png "access plus 1 year"
                ExpiresByType image/gif "access plus 1 year"
                ExpiresByType image/webp "access plus 1 year"
                ExpiresByType image/avif "access plus 1 year"
                ExpiresByType image/svg+xml "access plus 1 year"
                ExpiresByType image/x-icon "access plus 1 year"
                ExpiresByType font/woff2 "access plus 1 year"
                ExpiresByType font/woff "access plus 1 year"
                ExpiresByType text/css "access plus 1 month"
                ExpiresByType application/javascript "access plus 1 month"
                ExpiresByType text/javascript "access plus 1 month"
                ExpiresByType video/mp4 "access plus 1 month"
            </IfModule>
            CONF
              a2enconf -q zz-ateneya-cache
              if apache2ctl configtest; then systemctl reload apache2 && echo 'cache do browser ligada'; else a2disconf -q zz-ateneya-cache; echo 'configtest falhou: desfeito'; exit 1; fi
            elif command -v nginx >/dev/null 2>&1; then
              F=/etc/nginx/conf.d/zz-ateneya-cache.conf
              cat > "$F" <<'CONF'
            # Escrito pelo painel gestao.ateneya.com (Velocidade). Apagar e recarregar o nginx desfaz.
            # Validade por tipo de conteúdo; o HTML (páginas) fica como estava.
            map $sent_http_content_type $ateneya_expires {
                default                    off;
                ~^image/                   1y;
                ~^font/                    1y;
                application/font-woff2     1y;
                application/vnd.ms-fontobject 1y;
                ~^text/css                 30d;
                ~javascript                30d;
                video/mp4                  30d;
            }
            expires $ateneya_expires;
            CONF
              if nginx -t 2>&1; then systemctl reload nginx && echo 'cache do browser ligada'; else rm -f "$F"; echo 'nginx -t falhou: desfeito'; exit 1; fi
            else
              echo 'Não encontrei Apache nem nginx.'; exit 1
            fi
            SH,
            perigo: 'CSS/JS ficam em cache 1 mês no browser. O WordPress põe ?ver= nos ficheiros, por isso uma actualização de tema/plugin continua a chegar logo.',
            aplicavelComPlesk: false,
        );

        $lista[] = new VerificacaoVelocidade(
            grupo: $g,
            chave: 'mysql_buffer',
            label: 'Memória do MySQL (innodb_buffer_pool_size)',
            severidade: 'importante',
            porque: 'Se as bases de dados não cabem no buffer do MySQL, cada página vai ao disco buscar dados em vez de os ter em memória.',
            comando: <<<'SH'
            mysql -NBe "SELECT CONCAT('buffer=', @@innodb_buffer_pool_size), CONCAT('dados=', COALESCE(SUM(data_length+index_length),0)) FROM information_schema.tables WHERE engine='InnoDB'" 2>/dev/null | tr '\t' '\n' || echo 'sem-acesso'
            free -b | awk '/^Mem:/ {print "ram=" $2}'
            SH,
            avaliar: function (string $s): array {
                $v = self::chaves($s);
                if (! isset($v['buffer'])) {
                    return ['estado' => 'aviso', 'detalhe' => 'Não deu para perguntar ao MySQL como root (sem acesso por socket?).'];
                }
                $buf = (int) $v['buffer'];
                $dados = (int) ($v['dados'] ?? 0);
                $ram = (int) ($v['ram'] ?? 0);
                $mb = fn ($b) => round($b / 1048576) . ' MB';
                $det = "buffer {$mb($buf)} · dados InnoDB {$mb($dados)} · RAM {$mb($ram)}";

                if ($buf < $dados && $buf < $ram * 0.35) {
                    return ['estado' => 'falha', 'detalhe' => "As bases de dados não cabem no buffer.\n{$det}"];
                }

                return ['estado' => 'ok', 'detalhe' => $det];
            },
            correcao: <<<'SH'
            dados=$(mysql -NBe "SELECT COALESCE(SUM(data_length+index_length),0) FROM information_schema.tables WHERE engine='InnoDB'") || exit 1
            ram=$(free -b | awk '/^Mem:/ {print $2}')
            alvo=$(( dados * 13 / 10 ))
            max=$(( ram * 35 / 100 ))
            [ "$alvo" -gt "$max" ] && alvo=$max
            [ "$alvo" -lt 268435456 ] && alvo=268435456
            mb=$(( (alvo / 134217728 + 1) * 128 ))
            printf '# Escrito pelo painel gestao.ateneya.com (Velocidade). Apagar repõe o valor por omissão.\n[mysqld]\ninnodb_buffer_pool_size = %sM\n' "$mb" > /etc/mysql/conf.d/zz-ateneya-velocidade.cnf
            echo "innodb_buffer_pool_size = ${mb}M"
            if systemctl list-units --type=service --no-legend | grep -q '^ *mariadb'; then systemctl restart mariadb; else systemctl restart mysql; fi
            mysql -NBe 'SELECT @@innodb_buffer_pool_size/1048576'
            SH,
            perigo: 'Reinicia o MySQL/MariaDB: todos os sites desta máquina ficam 5–20 s sem base de dados. Fazer fora de horas. O valor é no máximo 35% da RAM.',
            aplicavelComPlesk: false,
        );

        $lista[] = new VerificacaoVelocidade(
            grupo: $g,
            chave: 'fpm_limite',
            label: 'PHP-FPM a chegar ao limite de processos',
            severidade: 'importante',
            porque: 'Quando o pool chega ao pm.max_children, os pedidos seguintes ficam em fila — o site parece lento só em horas de ponta.',
            comando: "grep -h 'max_children' /var/log/php*-fpm.log 2>/dev/null | tail -6 || true",
            avaliar: fn (string $s): array => trim($s) === ''
                ? ['estado' => 'ok', 'detalhe' => 'nenhum registo de pool no limite']
                : ['estado' => 'aviso', 'detalhe' => "Pools a bater no limite (subir pm.max_children à mão, conforme a RAM):\n" . trim($s)],
            aplicavelComPlesk: false,
        );

        $lista[] = new VerificacaoVelocidade(
            grupo: $g,
            chave: 'wpcli',
            label: 'WP-CLI instalado',
            severidade: 'info',
            porque: 'As verificações e correcções de cada site WordPress usam o WP-CLI.',
            comando: 'command -v wp >/dev/null 2>&1 && wp --allow-root --version 2>/dev/null || echo SEM-WPCLI',
            avaliar: fn (string $s): array => str_contains($s, 'SEM-WPCLI') || trim($s) === ''
                ? ['estado' => 'falha', 'detalhe' => 'Sem WP-CLI: as verificações por site não conseguem correr.']
                : ['estado' => 'ok', 'detalhe' => trim($s)],
            correcao: 'curl -fsSL -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod 755 /usr/local/bin/wp && wp --allow-root --version',
        );

        $porChave = [];
        foreach ($lista as $v) {
            $porChave[$v->chave] = $v;
        }

        return $porChave;
    }

    // ------------------------------------------------------------------
    // Cada site WordPress
    // ------------------------------------------------------------------

    /** @return array<int, VerificacaoVelocidade> */
    private static function doSite(Site $site): array
    {
        $dominio = strtolower(preg_replace('/^www\./', '', trim($site->domain)));
        $grupo = $dominio;
        $id = $site->id;

        $lista = [];

        $lista[] = new VerificacaoVelocidade(
            grupo: $grupo,
            chave: "site{$id}_ttfb",
            label: 'Tempo de resposta na própria máquina (TTFB)',
            severidade: 'critica',
            porque: 'Medido dentro do servidor, sem rede pelo meio: é o tempo que o PHP/WordPress demora a gerar a página. Com cache de página deve ficar abaixo de 0,3 s.',
            comando: self::script($site, <<<'SH'
            for i in 1 2 3 4; do
              curl -sk -L -o /dev/null --max-time 30 --resolve {{DOM}}:443:127.0.0.1 --resolve www.{{DOM}}:443:127.0.0.1 -w '%{time_starttransfer} %{http_code}\n' https://{{DOM}}/
            done
            curl -sk -L -D - -o /dev/null --max-time 30 --resolve {{DOM}}:443:127.0.0.1 --resolve www.{{DOM}}:443:127.0.0.1 https://{{DOM}}/ | grep -iE '^(x-cache|x-.*cache|cache-control|age|server-timing):' | head -5
            SH, precisaWp: false),
            avaliar: function (string $s): array {
                $tempos = [];
                $codigos = [];
                $extra = [];
                foreach (explode("\n", trim($s)) as $linha) {
                    if (preg_match('/^(\d+\.\d+) (\d{3})$/', trim($linha), $m)) {
                        $tempos[] = (float) $m[1];
                        $codigos[] = $m[2];
                    } elseif (trim($linha) !== '') {
                        $extra[] = trim($linha);
                    }
                }
                if (! $tempos) {
                    return ['estado' => 'aviso', 'detalhe' => 'Não deu para medir na máquina. ' . trim($s)];
                }
                $primeiro = $tempos[0];
                $resto = array_slice($tempos, 1) ?: $tempos;
                sort($resto);
                $mediana = $resto[intdiv(count($resto), 2)];
                $ms = fn ($t) => round($t * 1000) . ' ms';
                $det = "1.º pedido {$ms($primeiro)} · seguintes (mediana) {$ms($mediana)} · HTTP " . implode('/', array_unique($codigos));
                if ($extra) {
                    $det .= "\n" . implode("\n", $extra);
                }

                return match (true) {
                    $mediana < 0.4 => ['estado' => 'ok', 'detalhe' => $det],
                    $mediana < 1.2 => ['estado' => 'aviso', 'detalhe' => $det],
                    default        => ['estado' => 'falha', 'detalhe' => $det],
                };
            },
        );

        $lista[] = new VerificacaoVelocidade(
            grupo: $grupo,
            chave: "site{$id}_cache_pagina",
            label: 'Cache de página',
            severidade: 'critica',
            porque: 'Guarda a página já gerada e serve-a sem correr o PHP nem ir à base de dados. É o que leva um WordPress de 2–5 s para menos de 0,3 s.',
            comando: self::script($site, <<<'SH'
            echo "activos=$(WP plugin list --status=active --field=name | tr '\n' ' ')"
            echo "wp_cache=$(WP config get WP_CACHE 2>/dev/null || echo nao-definido)"
            [ -f "$P/wp-content/advanced-cache.php" ] && echo 'advanced_cache=sim' || echo 'advanced_cache=nao'
            SH),
            avaliar: function (string $s): array {
                if ($r = self::semWp($s)) {
                    return $r;
                }
                $v = self::chaves($s);
                $activos = preg_split('/\s+/', trim($v['activos'] ?? '')) ?: [];
                $cache = array_values(array_intersect($activos, self::PLUGINS_CACHE));
                $woo = in_array('woocommerce', $activos, true) ? ' · WooCommerce activo' : '';

                if ($cache) {
                    $ligado = ($v['advanced_cache'] ?? '') === 'sim' || in_array('litespeed-cache', $cache, true) || in_array('w3-total-cache', $cache, true);

                    return $ligado
                        ? ['estado' => 'ok', 'detalhe' => 'plugin: ' . implode(', ', $cache) . $woo]
                        : ['estado' => 'aviso', 'detalhe' => 'Plugin de cache activo (' . implode(', ', $cache) . ') mas sem advanced-cache.php: confirma nas definições do plugin se a cache está mesmo ligada.' . $woo];
                }

                return ['estado' => 'falha', 'detalhe' => 'Nenhum plugin de cache de página activo.' . $woo];
            },
            correcao: self::script($site, <<<'SH'
            activos=" $(WP plugin list --status=active --field=name | tr '\n' ' ') "
            for p in wp-super-cache w3-total-cache wp-fastest-cache litespeed-cache wp-rocket breeze sg-cachepress comet-cache; do
              case "$activos" in *" $p "*) echo "Já há outro plugin de cache activo ($p): não instalo um segundo."; exit 1;; esac
            done
            WP plugin install cache-enabler --activate || exit 1
            WP config set WP_CACHE true --raw --type=constant
            [ -f "$P/wp-content/advanced-cache.php" ] && echo 'advanced-cache.php presente' || echo 'ATENÇÃO: sem advanced-cache.php (wp-content sem escrita para o dono do site?)'
            echo "Cache Enabler activo em {{DOM}}"
            SH),
            perigo: 'Instala e activa o plugin Cache Enabler (gratuito, KeyCDN). Os visitantes passam a ver a página guardada; guardar um artigo/página no WordPress limpa a cache sozinho. Utilizadores com sessão iniciada não são afectados. Em lojas WooCommerce o carrinho e o checkout nunca são guardados, mas testa uma compra depois.',
        );

        $lista[] = new VerificacaoVelocidade(
            grupo: $grupo,
            chave: "site{$id}_object_cache",
            label: 'Object cache (Redis)',
            severidade: 'importante',
            porque: 'Guarda em memória as consultas repetidas à base de dados — ajuda sobretudo nas páginas que não podem ir para a cache (admin, carrinho, pesquisa).',
            comando: self::script($site, <<<'SH'
            [ -f "$P/wp-content/object-cache.php" ] && echo "dropin=$(head -c 400 "$P/wp-content/object-cache.php" | grep -oiE 'redis|memcache|apcu' | head -1)" || echo 'dropin=nao'
            echo "redis=$(systemctl is-active redis-server 2>/dev/null || echo inactivo)"
            SH),
            avaliar: function (string $s): array {
                if ($r = self::semWp($s)) {
                    return $r;
                }
                $v = self::chaves($s);
                if (($v['dropin'] ?? 'nao') !== 'nao') {
                    return ['estado' => 'ok', 'detalhe' => 'object-cache.php presente (' . ($v['dropin'] ?: 'desconhecido') . ')'];
                }

                return ['estado' => 'aviso', 'detalhe' => ($v['redis'] ?? '') === 'active'
                    ? 'Redis a correr na máquina, mas este site não o usa.'
                    : 'Sem object cache. Liga primeiro o Redis na máquina (verificação "Redis").'];
            },
            correcao: self::script($site, <<<'SH'
            systemctl is-active --quiet redis-server || { echo 'O Redis não está a correr: corrige primeiro a verificação "Redis" da máquina.'; exit 1; }
            WP config set WP_REDIS_PREFIX '{{DOM}}:' --type=constant
            WP config set WP_CACHE_KEY_SALT '{{DOM}}:' --type=constant
            WP plugin install redis-cache --activate || exit 1
            WP redis enable --force && WP redis status | head -8
            SH),
            perigo: 'Instala o plugin Redis Object Cache e liga-o, com um prefixo próprio deste site (para não se misturar com os outros sites da máquina). Para desligar: Definições → Redis → Disable.',
        );

        $lista[] = new VerificacaoVelocidade(
            grupo: $grupo,
            chave: "site{$id}_wp_cron",
            label: 'wp-cron a correr nas visitas',
            severidade: 'info',
            porque: 'Por omissão o WordPress corre as tarefas agendadas durante as visitas, o que torna algumas páginas lentas ao acaso.',
            comando: self::script($site, <<<'SH'
            echo "disable=$(WP config get DISABLE_WP_CRON 2>/dev/null || echo nao-definido)"
            crontab -u "$U" -l 2>/dev/null | grep -q -- "--path=$P" && echo 'crontab=sim' || echo 'crontab=nao'
            SH),
            avaliar: function (string $s): array {
                if ($r = self::semWp($s)) {
                    return $r;
                }
                $v = self::chaves($s);
                $desligado = in_array(strtolower($v['disable'] ?? ''), ['1', 'true'], true);

                return match (true) {
                    $desligado && ($v['crontab'] ?? '') === 'sim' => ['estado' => 'ok', 'detalhe' => 'wp-cron desligado nas visitas e a correr pelo cron do sistema'],
                    $desligado => ['estado' => 'falha', 'detalhe' => 'DISABLE_WP_CRON ligado mas sem cron do sistema: as tarefas agendadas não estão a correr!'],
                    default    => ['estado' => 'aviso', 'detalhe' => 'wp-cron corre durante as visitas'],
                };
            },
            correcao: self::script($site, <<<'SH'
            WPBIN=$(command -v wp)
            (crontab -u "$U" -l 2>/dev/null | grep -v -- "--path=$P"; echo "*/5 * * * * $WPBIN --path=$P cron event run --due-now --quiet >/dev/null 2>&1") | crontab -u "$U" - || exit 1
            WP config set DISABLE_WP_CRON true --raw --type=constant
            echo "cron do sistema de 5 em 5 min para $P (utilizador $U)"
            SH),
            perigo: 'Passa as tarefas agendadas do WordPress (publicação agendada, e-mails, backups de plugins) para o cron do sistema, de 5 em 5 minutos.',
        );

        $lista[] = new VerificacaoVelocidade(
            grupo: $grupo,
            chave: "site{$id}_autoload",
            label: 'Opções carregadas em todas as páginas (autoload)',
            severidade: 'importante',
            porque: 'O WordPress carrega as opções "autoload" em cada pedido. Plugins desinstalados deixam lá lixo — acima de 1 MB já se nota.',
            comando: self::script($site, <<<'SH'
            T="$(WP db prefix)options"
            echo "total_kb=$(WP db query "SELECT ROUND(SUM(LENGTH(option_value))/1024) FROM $T WHERE autoload IN ('yes','on','auto','auto-on')" --skip-column-names)"
            WP db query "SELECT CONCAT(option_name, ' ', ROUND(LENGTH(option_value)/1024), ' KB') FROM $T WHERE autoload IN ('yes','on','auto','auto-on') ORDER BY LENGTH(option_value) DESC LIMIT 5" --skip-column-names
            SH),
            avaliar: function (string $s): array {
                if ($r = self::semWp($s)) {
                    return $r;
                }
                $v = self::chaves($s);
                $kb = (int) ($v['total_kb'] ?? 0);
                $det = "{$kb} KB em autoload. Maiores:\n" . trim(preg_replace('/^total_kb=.*$/m', '', $s));

                return match (true) {
                    $kb > 3000 => ['estado' => 'falha', 'detalhe' => $det],
                    $kb > 1000 => ['estado' => 'aviso', 'detalhe' => $det],
                    default    => ['estado' => 'ok', 'detalhe' => "{$kb} KB"],
                };
            },
        );

        $lista[] = new VerificacaoVelocidade(
            grupo: $grupo,
            chave: "site{$id}_plugins",
            label: 'Plugins activos',
            severidade: 'info',
            porque: 'Cada plugin corre em cada página que não vem da cache. Muitos plugins (ou construtores pesados) pesam no tempo de resposta.',
            comando: self::script($site, <<<'SH'
            WP plugin list --status=active --field=name | tr '\n' ' '
            SH),
            avaliar: function (string $s): array {
                if ($r = self::semWp($s)) {
                    return $r;
                }
                $nomes = preg_split('/\s+/', trim($s), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $n = count($nomes);
                $det = "{$n} activos: " . implode(', ', $nomes);

                return $n > 35
                    ? ['estado' => 'aviso', 'detalhe' => $det]
                    : ['estado' => 'ok', 'detalhe' => $det];
            },
        );

        return $lista;
    }

    /**
     * Embrulha um script de site: descobre a pasta do WordPress, o dono dela,
     * e define WP() para correr o WP-CLI como esse dono (runuser, que existe
     * sempre em Debian/Ubuntu, ao contrário do sudo) (os ficheiros de
     * cache ficam com o dono certo). Corre numa subshell para o `exit` não
     * matar o resto da auditoria, que vai toda na mesma ligação.
     */
    private static function script(Site $site, string $corpo, bool $precisaWp = true): string
    {
        $dominio = strtolower(preg_replace('/^www\./', '', trim($site->domain)));

        $candidatos = array_filter([
            $site->wp_root,
            "/var/www/{$dominio}/public_html",
            "/var/www/{$dominio}/htdocs",
            "/var/www/{$dominio}",
            "/var/www/vhosts/{$dominio}/httpdocs",
            "/var/www/www.{$dominio}/public_html",
        ]);
        $candidatos = implode(' ', array_map('escapeshellarg', array_unique($candidatos)));

        $prelude = <<<'SH'
        P=""; for d in {{CANDIDATOS}}; do [ -f "$d/wp-config.php" ] && { P="$d"; break; }; done
        SH;

        if ($precisaWp) {
            $prelude .= "\n" . <<<'SH'
            [ -n "$P" ] || { echo 'SEM-WP'; exit 0; }
            command -v wp >/dev/null 2>&1 || { echo 'SEM-WPCLI'; exit 0; }
            U=$(stat -c %U "$P")
            WPBIN_=$(command -v wp)
            WP() { runuser -u "$U" -- env HOME=/tmp "$WPBIN_" --path="$P" --allow-root "$@" 2>/dev/null; }
            SH;
        }

        $script = $prelude . "\n" . $corpo;

        // O domínio vem da BD; só deixamos passar caracteres de domínio.
        $domSeguro = preg_replace('/[^a-z0-9.\-]/', '', $dominio);

        return "(\n" . strtr($script, ['{{CANDIDATOS}}' => $candidatos, '{{DOM}}' => $domSeguro]) . "\n)";
    }

    private static function semWp(string $s): ?array
    {
        return match (true) {
            str_contains($s, 'SEM-WPCLI') => ['estado' => 'aviso', 'detalhe' => 'Sem WP-CLI na máquina: corrige primeiro a verificação "WP-CLI instalado".'],
            str_contains($s, 'SEM-WP')    => ['estado' => 'aviso', 'detalhe' => 'Não encontrei o WordPress deste site na máquina. Preenche o "wp_root" na ficha do site.'],
            default                       => null,
        };
    }

    /** Lê linhas "chave=valor" da saída. */
    private static function chaves(string $s): array
    {
        $v = [];
        foreach (preg_split('/\R/', $s) ?: [] as $linha) {
            if (preg_match('/^([a-z_]+)=(.*)$/', trim($linha), $m)) {
                $v[$m[1]] = trim($m[2]);
            }
        }

        return $v;
    }
}
