<?php

namespace App\Services\Seguranca;

use App\Models\Server;

/**
 * O que se verifica em cada servidor, e como se corrige.
 *
 * Tudo escrito para Debian/Ubuntu com Apache, que é o que está debaixo destas
 * máquinas. As verificações são todas de leitura; as correcções estão à vista
 * e só correm quando alguém carrega no botão.
 *
 * Nos servidores com Plesk há verificações que ficam de fora (`aplicavelComPlesk`):
 * a configuração do Apache e do firewall é gerada pelo painel e mexer nela à
 * mão dá conflito na próxima vez que o Plesk reescrever os ficheiros.
 */
class CatalogoEndurecimento
{
    /** Ficheiro onde vivem as nossas regras de Apache, para não sujar as do sistema. */
    private const CONF_APACHE = '/etc/apache2/conf-available/endurecimento.conf';

    /** @return array<string, Verificacao> */
    public static function para(Server $server): array
    {
        $porta = (int) ($server->port ?: 22);

        $verificacoes = [
            new Verificacao(
                chave: 'ssh_root',
                label: 'Entrada do root por senha',
                severidade: 'critica',
                porque: 'Root com senha é o primeiro sítio onde toda a Internet bate. Com chave, deixa de haver o que adivinhar.',
                comando: "sshd -T 2>/dev/null | grep -i '^permitrootlogin' || grep -iE '^[[:space:]]*PermitRootLogin' /etc/ssh/sshd_config 2>/dev/null || echo 'SEM-LEITURA'",
                avaliar: function (string $s): array {
                    $v = strtolower($s);

                    if ($v === '' || str_contains($v, 'sem-leitura')) {
                        return ['estado' => 'aviso', 'detalhe' => 'Não deu para ler a configuração do SSH — confirma à mão.'];
                    }

                    if (str_contains($v, 'yes')) {
                        return ['estado' => 'falha', 'detalhe' => 'PermitRootLogin yes — o root entra com senha.'];
                    }

                    return ['estado' => 'ok', 'detalhe' => trim($s)];
                },
                correcao: self::sshDefine('PermitRootLogin', 'prohibit-password'),
                perigo: 'Depois disto o root só entra por chave. Confirma primeiro que a tua chave já te deixa entrar neste servidor.',
            ),

            new Verificacao(
                chave: 'ssh_senha',
                label: 'Autenticação por senha no SSH',
                severidade: 'critica',
                porque: 'Com senha desligada, um ataque de força bruta deixa de ter por onde entrar.',
                comando: "sshd -T 2>/dev/null | grep -i '^passwordauthentication' || grep -iE '^[[:space:]]*PasswordAuthentication' /etc/ssh/sshd_config 2>/dev/null || echo 'SEM-LEITURA'",
                avaliar: function (string $s): array {
                    $v = strtolower($s);

                    if ($v === '' || str_contains($v, 'sem-leitura')) {
                        return ['estado' => 'aviso', 'detalhe' => 'Não deu para ler a configuração do SSH — confirma à mão.'];
                    }

                    if (str_contains($v, 'yes')) {
                        return ['estado' => 'falha', 'detalhe' => 'PasswordAuthentication yes — dá para tentar senhas à sorte.'];
                    }

                    return ['estado' => 'ok', 'detalhe' => trim($s) ?: 'desligada'];
                },
                correcao: self::sshDefine('PasswordAuthentication', 'no'),
                perigo: 'Só avança se TODAS as contas que usas neste servidor já entram por chave — incluindo as dos estagiários. Caso contrário ficas fechado de fora.',
            ),

            new Verificacao(
                chave: 'fail2ban',
                label: 'fail2ban a correr',
                severidade: 'importante',
                porque: 'Bloqueia sozinho o IP que falha logins a repetir. É o que cala os robôs.',
                comando: 'systemctl is-active fail2ban 2>/dev/null || echo inactive',
                avaliar: fn (string $s): array => str_starts_with(trim($s), 'active')
                    ? ['estado' => 'ok', 'detalhe' => 'activo']
                    : ['estado' => 'falha', 'detalhe' => 'não está instalado ou não está a correr'],
                correcao: 'apt-get update -qq && apt-get install -y fail2ban && systemctl enable --now fail2ban && systemctl is-active fail2ban',
            ),

            new Verificacao(
                chave: 'firewall',
                label: 'Firewall activa (ufw)',
                severidade: 'importante',
                porque: 'Sem firewall, tudo o que um serviço abra por engano fica exposto à Internet.',
                comando: "ufw status 2>/dev/null | head -1 || echo 'ufw nao instalado'",
                avaliar: fn (string $s): array => str_contains(strtolower($s), 'active') && ! str_contains(strtolower($s), 'inactive')
                    ? ['estado' => 'ok', 'detalhe' => trim($s)]
                    : ['estado' => 'falha', 'detalhe' => trim($s) ?: 'sem firewall'],
                correcao: "apt-get install -y ufw && ufw allow {$porta}/tcp && ufw allow 80/tcp && ufw allow 443/tcp && ufw --force enable && ufw status",
                perigo: "A porta SSH ({$porta}) é aberta antes de a firewall ser activada. Se usas outras portas (base de dados, painel, etc.), abre-as primeiro à mão.",
                aplicavelComPlesk: false,
            ),

            new Verificacao(
                chave: 'updates_automaticos',
                label: 'Actualizações de segurança automáticas',
                severidade: 'importante',
                porque: 'A maioria das invasões entra por uma falha já corrigida há meses.',
                comando: 'systemctl is-active unattended-upgrades 2>/dev/null || echo inactive',
                avaliar: fn (string $s): array => str_starts_with(trim($s), 'active')
                    ? ['estado' => 'ok', 'detalhe' => 'activo']
                    : ['estado' => 'falha', 'detalhe' => 'as actualizações de segurança não estão a ser aplicadas sozinhas'],
                correcao: 'apt-get update -qq && apt-get install -y unattended-upgrades && dpkg-reconfigure -f noninteractive unattended-upgrades && systemctl enable --now unattended-upgrades && systemctl is-active unattended-upgrades',
            ),

            new Verificacao(
                chave: 'updates_pendentes',
                label: 'Actualizações de segurança por instalar',
                severidade: 'importante',
                porque: 'Pacotes por actualizar são falhas conhecidas à espera.',
                comando: "apt-get update -qq >/dev/null 2>&1; apt-get -s upgrade 2>/dev/null | grep -c '^Inst.*[Ss]ecurity'",
                avaliar: function (string $s): array {
                    $n = (int) trim($s);

                    return $n === 0
                        ? ['estado' => 'ok', 'detalhe' => 'nenhuma pendente']
                        : ['estado' => 'aviso', 'detalhe' => "{$n} actualizações de segurança por instalar"];
                },
                correcao: 'apt-get update && apt-get -y upgrade && echo "--- por instalar depois:" && apt-get -s upgrade 2>/dev/null | grep -c "^Inst.*[Ss]ecurity"',
                perigo: 'Actualizar pode reiniciar serviços (Apache, PHP, MySQL). Há sites a cair durante alguns segundos.',
            ),

            new Verificacao(
                chave: 'contas_uid0',
                label: 'Contas com poderes de root',
                severidade: 'critica',
                porque: 'Uma segunda conta com UID 0 é a porta das traseiras clássica de quem já entrou.',
                comando: "awk -F: '\$3==0 {print \$1}' /etc/passwd | grep -v '^root\$' || echo nenhum",
                avaliar: function (string $s): array {
                    $s = trim($s);

                    return ($s === '' || $s === 'nenhum')
                        ? ['estado' => 'ok', 'detalhe' => 'só o root']
                        : ['estado' => 'falha', 'detalhe' => "além do root: {$s}"];
                },
                correcao: null,
            ),

            new Verificacao(
                chave: 'mysql_exposto',
                label: 'Base de dados aberta à Internet',
                severidade: 'critica',
                porque: 'O MySQL só precisa de ser ouvido pela própria máquina. À escuta em 0.0.0.0 é um convite.',
                comando: "ss -lnt 2>/dev/null | awk '{print \$4}' | grep -E ':3306\$' | grep -vE '^(127\\.0\\.0\\.1|\\[::1\\]|\\[::ffff:127\\.0\\.0\\.1\\])' || echo nenhum",
                avaliar: function (string $s): array {
                    $s = trim($s);

                    return ($s === '' || $s === 'nenhum')
                        ? ['estado' => 'ok', 'detalhe' => 'só escuta localmente']
                        : ['estado' => 'falha', 'detalhe' => "à escuta em {$s}"];
                },
                correcao: "printf '[mysqld]\\nbind-address = 127.0.0.1\\n' > /etc/mysql/mysql.conf.d/99-bind-local.cnf && (systemctl restart mysql 2>/dev/null || systemctl restart mariadb) && ss -lnt | grep 3306",
                perigo: 'Se alguma aplicação liga a esta base de dados de fora da máquina, deixa de ligar.',
            ),

            new Verificacao(
                chave: 'apache_default',
                label: 'Site por omissão do Apache activo',
                severidade: 'importante',
                porque: 'O vhost 000-default serve /var/www/html a quem bater pelo IP — e apanha tudo o que não tenha domínio certo.',
                comando: "ls /etc/apache2/sites-enabled/ 2>/dev/null | grep -E '^000-default' || echo nenhum",
                avaliar: function (string $s): array {
                    $s = trim($s);

                    return ($s === '' || $s === 'nenhum')
                        ? ['estado' => 'ok', 'detalhe' => 'desactivado']
                        : ['estado' => 'aviso', 'detalhe' => "activo: {$s}"];
                },
                correcao: 'a2dissite 000-default 000-default-le-ssl 2>/dev/null; apache2ctl configtest && systemctl reload apache2 && echo feito',
                perigo: 'Se algum site depende do vhost por omissão (por exemplo, acesso pelo IP), deixa de responder.',
                aplicavelComPlesk: false,
            ),

            new Verificacao(
                chave: 'apache_assinatura',
                label: 'Apache a anunciar versão',
                severidade: 'info',
                porque: 'Dizer a versão exacta do Apache e do SO é dar o trabalho de casa feito a quem procura alvos.',
                comando: "grep -rhiE '^[[:space:]]*(ServerTokens|ServerSignature)' /etc/apache2/apache2.conf /etc/apache2/conf-enabled/ 2>/dev/null | sort -u || echo 'sem definicao'",
                avaliar: function (string $s): array {
                    $v = strtolower($s);
                    $ok = str_contains($v, 'servertokens prod') && str_contains($v, 'serversignature off');

                    return $ok
                        ? ['estado' => 'ok', 'detalhe' => trim($s)]
                        : ['estado' => 'aviso', 'detalhe' => trim($s) ?: 'por definir'];
                },
                correcao: self::confApache(),
                aplicavelComPlesk: false,
            ),

            new Verificacao(
                chave: 'apache_listagem',
                label: 'Listagem de pastas ligada',
                severidade: 'importante',
                porque: 'Com Indexes ligado, quem entre numa pasta sem index.php vê a lista dos ficheiros todos.',
                comando: "grep -rhE '^[[:space:]]*Options[^#]*[[:space:]]Indexes' /etc/apache2/apache2.conf /etc/apache2/conf-enabled/ /etc/apache2/sites-enabled/ 2>/dev/null | grep -v -- '-Indexes' | head -5 || echo nenhum",
                avaliar: function (string $s): array {
                    $s = trim($s);

                    return ($s === '' || $s === 'nenhum')
                        ? ['estado' => 'ok', 'detalhe' => 'desligada']
                        : ['estado' => 'falha', 'detalhe' => $s];
                },
                correcao: self::confApache(),
                aplicavelComPlesk: false,
            ),

            new Verificacao(
                chave: 'php_producao',
                label: 'PHP em modo de produção',
                severidade: 'importante',
                porque: 'display_errors mostra caminhos e senhas em páginas de erro; expose_php anuncia a versão em cada resposta.',
                comando: "for f in /etc/php/*/fpm/php.ini /etc/php/*/apache2/php.ini /opt/plesk/php/*/etc/php.ini; do [ -f \"\$f\" ] && grep -HE '^(expose_php|display_errors)[[:space:]]*=' \"\$f\"; done 2>/dev/null || echo 'sem php.ini'",
                avaliar: function (string $s): array {
                    $s = trim($s);

                    if ($s === '' || str_contains($s, 'sem php.ini')) {
                        return ['estado' => 'aviso', 'detalhe' => 'não foi encontrado php.ini nos sítios do costume'];
                    }

                    $mal = array_values(array_filter(
                        explode("\n", $s),
                        fn (string $linha) => preg_match('/=\s*(on|1|true|stderr|stdout)\s*$/i', $linha) === 1,
                    ));

                    return $mal === []
                        ? ['estado' => 'ok', 'detalhe' => 'expose_php e display_errors desligados']
                        : ['estado' => 'falha', 'detalhe' => implode("\n", $mal)];
                },
                correcao: 'for f in /etc/php/*/fpm/php.ini /etc/php/*/apache2/php.ini /opt/plesk/php/*/etc/php.ini; do [ -f "$f" ] && sed -i -e "s/^expose_php[[:space:]]*=.*/expose_php = Off/" -e "s/^display_errors[[:space:]]*=.*/display_errors = Off/" "$f"; done; for s in $(systemctl list-units --type=service --no-legend "php*-fpm*" 2>/dev/null | awk "{print \$1}"); do systemctl reload "$s"; done; systemctl reload apache2 2>/dev/null; echo feito',
            ),

            new Verificacao(
                chave: 'escrita_todos',
                label: 'Pastas do site com escrita para toda a gente',
                severidade: 'importante',
                porque: 'Uma pasta 777 deixa qualquer processo da máquina — incluindo o PHP de outro site — escrever lá dentro.',
                comando: "find /var/www -maxdepth 5 -type d -perm -o+w ! -perm -1000 2>/dev/null | head -20 || echo nenhum",
                avaliar: function (string $s): array {
                    $s = trim($s);

                    if ($s === '' || $s === 'nenhum') {
                        return ['estado' => 'ok', 'detalhe' => 'nenhuma'];
                    }

                    $linhas = substr_count($s, "\n") + 1;

                    return ['estado' => 'falha', 'detalhe' => "{$linhas} pasta(s):\n{$s}"];
                },
                correcao: 'find /var/www -type d -perm -o+w ! -perm -1000 -exec chmod o-w {} + 2>/dev/null; find /var/www -type f -perm -o+w -exec chmod o-w {} + 2>/dev/null; echo feito',
                perigo: 'Se algum plugin de WordPress dependia de escrita livre, pode passar a pedir credenciais FTP para actualizar.',
            ),

            new Verificacao(
                chave: 'wp_config',
                label: 'wp-config.php legível por todos',
                severidade: 'critica',
                porque: 'O wp-config.php tem a senha da base de dados. Legível por qualquer utilizador da máquina é a senha entregue.',
                comando: "find /var/www -maxdepth 5 -name wp-config.php -perm -o+r 2>/dev/null | head -20 || echo nenhum",
                avaliar: function (string $s): array {
                    $s = trim($s);

                    return ($s === '' || $s === 'nenhum')
                        ? ['estado' => 'ok', 'detalhe' => 'nenhum legível por todos']
                        : ['estado' => 'falha', 'detalhe' => $s];
                },
                correcao: 'find /var/www -maxdepth 5 -name wp-config.php -exec chmod 640 {} + 2>/dev/null; echo feito',
            ),

            new Verificacao(
                chave: 'certificados',
                label: 'Certificados SSL a expirar',
                severidade: 'importante',
                porque: 'Um certificado que expira põe o aviso do browser à frente do site do cliente.',
                comando: 'for d in /etc/letsencrypt/live/*/; do [ -f "$d/cert.pem" ] && echo "$(basename $d)|$(openssl x509 -enddate -noout -in "$d/cert.pem" | cut -d= -f2)"; done 2>/dev/null || echo nenhum',
                avaliar: function (string $s): array {
                    $s = trim($s);

                    if ($s === '' || $s === 'nenhum') {
                        return ['estado' => 'info', 'detalhe' => 'sem certificados Let’s Encrypt nesta máquina'];
                    }

                    $aviso = [];
                    $ok    = [];

                    foreach (explode("\n", $s) as $linha) {
                        [$dominio, $data] = array_pad(explode('|', trim($linha), 2), 2, '');
                        $ts = strtotime($data);

                        if (! $ts) {
                            continue;
                        }

                        $dias = (int) floor(($ts - time()) / 86400);

                        if ($dias <= 21) {
                            $aviso[] = "{$dominio}: {$dias} dias";
                        } else {
                            $ok[] = "{$dominio}: {$dias} dias";
                        }
                    }

                    return $aviso === []
                        ? ['estado' => 'ok', 'detalhe' => implode("\n", $ok)]
                        : ['estado' => 'aviso', 'detalhe' => implode("\n", array_merge($aviso, $ok))];
                },
                correcao: 'certbot renew --quiet; systemctl reload apache2 2>/dev/null; certbot certificates 2>/dev/null | grep -E "Certificate Name|Expiry"',
            ),

            new Verificacao(
                chave: 'portas',
                label: 'Portas à escuta',
                severidade: 'info',
                porque: 'Serve para veres o que está aberto para fora e perguntares se tem de estar.',
                comando: "ss -lntp 2>/dev/null | awk 'NR>1 {print \$4\"  \"\$6}' | sed 's/users:((//; s/))\$//' | sort -u | head -25",
                avaliar: fn (string $s): array => ['estado' => 'info', 'detalhe' => trim($s) ?: 'não deu para ler'],
                correcao: null,
            ),
        ];

        // Em Plesk fica de fora o que o painel gere — mexer nisso à mão dá
        // conflito assim que o Plesk reescrever os ficheiros.
        if ($server->hasPlesk()) {
            $verificacoes = array_filter($verificacoes, fn (Verificacao $v) => $v->aplicavelComPlesk);
        }

        $indexadas = [];

        foreach ($verificacoes as $v) {
            $indexadas[$v->chave] = $v;
        }

        return $indexadas;
    }

    public static function verificacao(Server $server, string $chave): ?Verificacao
    {
        return self::para($server)[$chave] ?? null;
    }

    /**
     * Mexer no sshd com rede pelo meio pede cuidado: escreve-se num ficheiro
     * à parte (quando o sshd suporta Include), testa-se com `sshd -t` e só se
     * recarrega se o teste passar. A sessão aberta não cai com um reload.
     */
    private static function sshDefine(string $chave, string $valor): string
    {
        return implode(' ', [
            'D=/etc/ssh/sshd_config.d/99-endurecimento.conf;',
            'if grep -qE "^[[:space:]]*Include[[:space:]]+/etc/ssh/sshd_config.d/\*.conf" /etc/ssh/sshd_config; then',
            'mkdir -p /etc/ssh/sshd_config.d && touch "$D" &&',
            "sed -i '/^{$chave}/d' \"\$D\" && echo '{$chave} {$valor}' >> \"\$D\";",
            'else',
            'cp /etc/ssh/sshd_config "/etc/ssh/sshd_config.bak.$(date +%s)" &&',
            "sed -i 's/^[[:space:]]*#\\?[[:space:]]*{$chave}.*/{$chave} {$valor}/' /etc/ssh/sshd_config &&",
            "(grep -qE '^{$chave}' /etc/ssh/sshd_config || echo '{$chave} {$valor}' >> /etc/ssh/sshd_config);",
            'fi;',
            'sshd -t && (systemctl reload ssh 2>/dev/null || systemctl reload sshd) && sshd -T | grep -i',
            strtolower($chave),
        ]);
    }

    /** As nossas regras de Apache, num ficheiro só nosso. Correr outra vez não faz mal. */
    private static function confApache(): string
    {
        $conf = self::CONF_APACHE;

        return <<<SHELL
        cat > {$conf} <<'FIM'
        # Escrito pelo painel da Ateneya — regras de endurecimento.
        ServerTokens Prod
        ServerSignature Off
        TraceEnable Off

        <Directory /var/www/>
            Options -Indexes +FollowSymLinks
        </Directory>
        FIM
        a2enconf endurecimento >/dev/null 2>&1; apache2ctl configtest && systemctl reload apache2 && echo feito
        SHELL;
    }
}
