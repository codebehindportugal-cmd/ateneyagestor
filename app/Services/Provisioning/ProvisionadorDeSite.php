<?php

namespace App\Services\Provisioning;

use App\Models\Credential;
use App\Models\Site;
use App\Models\SiteProvision;
use App\Services\Cofre\CofreCrypto;
use App\Services\SshService;
use Illuminate\Support\Str;

/**
 * Monta um site novo num VPS a partir do domínio.
 *
 * A receita, por ordem: utilizador de sistema próprio → pastas → pool de
 * PHP-FPM só dele → vhost do Apache → base de dados e utilizador → SSL →
 * WordPress. No fim, as senhas ficam no cofre e o site fica registado no
 * painel (para as cópias passarem a apanhá-lo).
 *
 * O que distingue isto de um script à pressa: cada site corre com o seu
 * utilizador e com o seu pool, com open_basedir apertado. Um WordPress
 * comprometido fica preso à sua pasta em vez de ler os outros sites todos da
 * máquina — que é o que acontece quando tudo corre como www-data.
 *
 * Nada disto é feito em servidores com Plesk: lá quem manda é o painel.
 */
class ProvisionadorDeSite
{
    public function __construct(private SshService $ssh)
    {
    }

    public function correr(SiteProvision $provisao): SiteProvision
    {
        $servidor = $provisao->server;
        $opcoes   = $provisao->opcoes ?? [];

        $provisao->update([
            'estado'     => 'a_correr',
            'comecou_em' => now(),
            'passos'     => [],
            'log'        => '',
        ]);

        $dominio = $this->normalizarDominio($provisao->dominio);
        $nomes   = $this->nomes($dominio);

        $senhaBd    = CofreCrypto::gerarSenha(28, simbolos: false);
        $senhaAdmin = CofreCrypto::gerarSenha(20, simbolos: false);

        try {
            if ($servidor->hasPlesk()) {
                throw new \RuntimeException(
                    'Este servidor tem Plesk. Criar o site à mão por fora do painel dá conflito assim que o Plesk reescrever a configuração — cria-o no Plesk e depois regista-o aqui.'
                );
            }

            $this->passo($provisao, 'verificar', 'Ver se o domínio já existe na máquina',
                "test -e /var/www/{$dominio} && echo EXISTE || echo LIVRE",
                validar: function (string $saida) use ($dominio) {
                    if (str_contains($saida, 'EXISTE')) {
                        throw new \RuntimeException("Já existe /var/www/{$dominio} nesta máquina.");
                    }
                });

            $php = $this->passo($provisao, 'php', 'Descobrir a versão do PHP',
                "ls -1 /etc/php 2>/dev/null | grep -E '^[0-9]+\\.[0-9]+$' | sort -V | tail -1",
                validar: function (string $saida) {
                    if (trim($saida) === '') {
                        throw new \RuntimeException('Não encontrei PHP-FPM instalado (/etc/php vazio).');
                    }
                });

            $php = trim($php);

            $this->passo($provisao, 'utilizador', 'Criar o utilizador de sistema do site',
                $this->comandoUtilizador($dominio, $nomes['utilizador']));

            $this->passo($provisao, 'pastas', 'Criar as pastas',
                $this->comandoPastas($dominio, $nomes['utilizador']));

            $this->passo($provisao, 'fpm', "Criar o pool de PHP-FPM ({$php})",
                $this->comandoPool($dominio, $nomes, $php));

            $this->passo($provisao, 'vhost', 'Criar o vhost do Apache',
                $this->comandoVhost($dominio, $nomes));

            $this->passo($provisao, 'base_dados', 'Criar a base de dados e o utilizador',
                $this->comandoBaseDados($nomes, $senhaBd));

            if ($opcoes['ssl'] ?? true) {
                $this->passo($provisao, 'ssl', 'Pedir o certificado Let’s Encrypt',
                    $this->comandoSsl($dominio, $opcoes['email'] ?? null),
                    opcional: true);
            }

            $protocolo = $this->temSsl($provisao) ? 'https' : 'http';

            if ($opcoes['wordpress'] ?? true) {
                $this->passo($provisao, 'wordpress', 'Instalar o WordPress',
                    $this->comandoWordPress($dominio, $nomes, $senhaBd, $senhaAdmin, $opcoes, $protocolo));
            }

            $this->passo($provisao, 'reiniciar', 'Recarregar o Apache e o PHP-FPM',
                "apache2ctl configtest && systemctl restart apache2 && systemctl reload php{$php}-fpm && echo feito");

            $site = $this->registarSite($provisao, $dominio, $nomes, $opcoes);

            $this->guardarNoCofre($provisao, $dominio, $nomes, $senhaBd, $senhaAdmin, $opcoes, $protocolo);

            $provisao->update([
                'estado'    => 'concluido',
                'site_id'   => $site->id,
                'acabou_em' => now(),
            ]);
        } catch (\Throwable $e) {
            $provisao->update([
                'estado'    => 'erro',
                'erro'      => $e->getMessage(),
                'acabou_em' => now(),
            ]);
        }

        return $provisao->refresh();
    }

    // ── Nomes ────────────────────────────────────────────────────────────────

    public function normalizarDominio(string $dominio): string
    {
        $dominio = strtolower(trim($dominio));
        $dominio = preg_replace('#^https?://#', '', $dominio) ?? $dominio;
        $dominio = rtrim(explode('/', $dominio)[0], '.');
        $dominio = preg_replace('/^www\./', '', $dominio) ?? $dominio;

        if (! preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]*[a-z0-9])?)+$/', $dominio)) {
            throw new \RuntimeException("Domínio inválido: {$dominio}");
        }

        return $dominio;
    }

    /**
     * Nomes derivados do domínio, curtos que baste: o utilizador do MySQL tem
     * 32 caracteres de tecto, e há domínios bem mais compridos do que isso.
     * O sufixo do hash evita que exemplo.pt e exemplo.com dêem no mesmo.
     */
    public function nomes(string $dominio): array
    {
        $base = Str::of($dominio)->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->limit(14, '')->trim('_')->toString();
        $base = $base.'_'.substr(md5($dominio), 0, 4);

        return [
            'base'       => $base,
            'utilizador' => 'w_'.$base,
            'bd'         => 'db_'.$base,
            'bd_user'    => 'u_'.$base,
            'socket'     => "/run/php/{$base}.sock",
        ];
    }

    // ── Comandos ─────────────────────────────────────────────────────────────

    private function comandoUtilizador(string $dominio, string $utilizador): string
    {
        return implode(' ', [
            "id -u {$utilizador} >/dev/null 2>&1 ||",
            "useradd --system --home-dir /var/www/{$dominio} --shell /usr/sbin/nologin --user-group {$utilizador};",
            // O Apache serve os ficheiros estáticos e precisa de os ler: entra
            // no grupo do site, e o grupo tem leitura. O www-data continua sem
            // poder escrever.
            "usermod -aG {$utilizador} www-data;",
            "id {$utilizador}",
        ]);
    }

    private function comandoPastas(string $dominio, string $utilizador): string
    {
        return implode(' && ', [
            "mkdir -p /var/www/{$dominio}/public_html /var/www/{$dominio}/logs /var/www/{$dominio}/tmp",
            "chown -R {$utilizador}:{$utilizador} /var/www/{$dominio}",
            "chmod 750 /var/www/{$dominio} /var/www/{$dominio}/public_html /var/www/{$dominio}/logs",
            "chmod 750 /var/www/{$dominio}/tmp",
            "ls -la /var/www/{$dominio}",
        ]);
    }

    private function comandoPool(string $dominio, array $nomes, string $php): string
    {
        $u      = $nomes['utilizador'];
        $socket = $nomes['socket'];
        $pool   = "/etc/php/{$php}/fpm/pool.d/{$dominio}.conf";

        return <<<SHELL
        cat > {$pool} <<'FIM'
        ; Pool próprio de {$dominio} — escrito pelo painel da Ateneya.
        [{$dominio}]
        user = {$u}
        group = {$u}
        listen = {$socket}
        listen.owner = www-data
        listen.group = www-data
        listen.mode = 0660

        pm = ondemand
        pm.max_children = 10
        pm.process_idle_timeout = 30s
        pm.max_requests = 500

        ; O site fica preso à sua pasta: mesmo comprometido, não lê os vizinhos.
        php_admin_value[open_basedir] = /var/www/{$dominio}:/tmp:/usr/share/php
        php_admin_value[upload_tmp_dir] = /var/www/{$dominio}/tmp
        php_admin_value[session.save_path] = /var/www/{$dominio}/tmp
        php_admin_value[error_log] = /var/www/{$dominio}/logs/php-error.log
        php_admin_flag[log_errors] = on
        php_admin_flag[display_errors] = off
        php_admin_value[disable_functions] = exec,passthru,shell_exec,system,proc_open,popen
        FIM
        php-fpm{$php} -t && systemctl reload php{$php}-fpm && echo pool-ok
        SHELL;
    }

    private function comandoVhost(string $dominio, array $nomes): string
    {
        $socket = $nomes['socket'];

        return <<<SHELL
        a2enmod proxy_fcgi setenvif rewrite headers >/dev/null 2>&1
        cat > /etc/apache2/sites-available/{$dominio}.conf <<'FIM'
        # {$dominio} — escrito pelo painel da Ateneya.
        <VirtualHost *:80>
            ServerName {$dominio}
            ServerAlias www.{$dominio}
            DocumentRoot /var/www/{$dominio}/public_html

            <Directory /var/www/{$dominio}/public_html>
                Options -Indexes +FollowSymLinks
                AllowOverride All
                Require all granted
            </Directory>

            <FilesMatch \.php$>
                SetHandler "proxy:unix:{$socket}|fcgi://localhost"
            </FilesMatch>

            # Ficheiros que não têm nada que ser servidos.
            <FilesMatch "^(wp-config\.php|\.env|\.git.*|.*\.sql|.*\.bak)$">
                Require all denied
            </FilesMatch>

            ErrorLog /var/www/{$dominio}/logs/error.log
            CustomLog /var/www/{$dominio}/logs/access.log combined
        </VirtualHost>
        FIM
        a2ensite {$dominio} >/dev/null && apache2ctl configtest && systemctl reload apache2 && echo vhost-ok
        SHELL;
    }

    private function comandoBaseDados(array $nomes, string $senha): string
    {
        $bd    = $nomes['bd'];
        $user  = $nomes['bd_user'];
        $senha = addslashes($senha);

        // Uma chamada só, com o SQL num heredoc: assim a senha não passa pela
        // linha de comandos e não fica na lista de processos.
        return <<<SHELL
        mysql <<'FIM'
        CREATE DATABASE IF NOT EXISTS `{$bd}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        CREATE USER IF NOT EXISTS '{$user}'@'localhost' IDENTIFIED BY '{$senha}';
        ALTER USER '{$user}'@'localhost' IDENTIFIED BY '{$senha}';
        GRANT ALL PRIVILEGES ON `{$bd}`.* TO '{$user}'@'localhost';
        FLUSH PRIVILEGES;
        FIM
        mysql -e "SHOW DATABASES LIKE '{$bd}';"
        SHELL;
    }

    private function comandoSsl(string $dominio, ?string $email): string
    {
        $email = $email ? escapeshellarg($email) : escapeshellarg('geral@ateneya.com');

        return <<<SHELL
        command -v certbot >/dev/null 2>&1 || { apt-get update -qq && apt-get install -y certbot python3-certbot-apache; }
        if ! getent hosts {$dominio} >/dev/null; then echo "SEM-DNS: {$dominio} ainda não resolve — o certificado fica para depois de apontares o domínio."; exit 0; fi
        certbot --apache -d {$dominio} -d www.{$dominio} --non-interactive --agree-tos -m {$email} --redirect || echo "CERTBOT-FALHOU: o site fica em http até o domínio apontar para cá."
        SHELL;
    }

    private function comandoWordPress(
        string $dominio,
        array $nomes,
        string $senhaBd,
        string $senhaAdmin,
        array $opcoes,
        string $protocolo,
    ): string {
        $u        = $nomes['utilizador'];
        $raiz     = "/var/www/{$dominio}/public_html";
        $email    = escapeshellarg($opcoes['email'] ?? 'geral@ateneya.com');
        $titulo   = escapeshellarg($opcoes['titulo'] ?? $dominio);
        $admin    = escapeshellarg($opcoes['admin'] ?? 'ateneya');
        $senhaAdm = escapeshellarg($senhaAdmin);
        $senhaBdQ = escapeshellarg($senhaBd);
        $locale   = escapeshellarg($opcoes['locale'] ?? 'pt_PT');

        return <<<SHELL
        set -e
        if ! command -v wp >/dev/null 2>&1; then
            curl -sS -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
            chmod +x /usr/local/bin/wp
        fi
        cd {$raiz}
        sudo -u {$u} -H wp core download --path={$raiz} --locale={$locale} --force
        sudo -u {$u} -H wp config create --path={$raiz} --dbname={$nomes['bd']} --dbuser={$nomes['bd_user']} --dbpass={$senhaBdQ} --dbhost=localhost --dbprefix=wp_ --skip-check --force
        sudo -u {$u} -H wp core install --path={$raiz} --url={$protocolo}://{$dominio} --title={$titulo} --admin_user={$admin} --admin_password={$senhaAdm} --admin_email={$email} --skip-email
        sudo -u {$u} -H wp rewrite structure '/%postname%/' --path={$raiz} --hard || true
        sudo -u {$u} -H wp plugin delete hello akismet --path={$raiz} || true
        chmod 640 {$raiz}/wp-config.php
        sudo -u {$u} -H wp core version --path={$raiz}
        SHELL;
    }

    // ── Depois de correr ─────────────────────────────────────────────────────

    private function temSsl(SiteProvision $provisao): bool
    {
        foreach ($provisao->passos ?? [] as $passo) {
            if (($passo['chave'] ?? '') === 'ssl') {
                $saida = $passo['saida'] ?? '';

                return ! str_contains($saida, 'SEM-DNS') && ! str_contains($saida, 'CERTBOT-FALHOU');
            }
        }

        return false;
    }

    private function registarSite(SiteProvision $provisao, string $dominio, array $nomes, array $opcoes): Site
    {
        return Site::create([
            'server_id'        => $provisao->server_id,
            'client_id'        => $provisao->client_id,
            'name'             => $dominio,
            'domain'           => $dominio,
            'login_url'        => ($opcoes['wordpress'] ?? true) ? "https://{$dominio}/wp-admin" : null,
            'type'             => ($opcoes['wordpress'] ?? true) ? 'wordpress' : 'vps_laravel',
            'is_active'        => true,
            'backup_frequency' => 'daily',
            'wp_root'          => ($opcoes['wordpress'] ?? true) ? "/var/www/{$dominio}/public_html" : null,
            'app_path'         => "/var/www/{$dominio}",
            'updates_enabled'  => (bool) ($opcoes['wordpress'] ?? true),
            'notes'            => "Criado pelo painel em ".now()->format('d/m/Y').
                ". Utilizador de sistema {$nomes['utilizador']}, pool PHP-FPM próprio, base de dados {$nomes['bd']}.",
        ]);
    }

    /**
     * As senhas vão para o cofre de credenciais da casa (cifrado com a
     * APP_KEY), e não para o cofre pessoal: são da empresa, têm de estar à
     * mão de quem tomar conta do site, e são escritas por um processo que não
     * tem — nem deve ter — master password nenhuma.
     */
    private function guardarNoCofre(
        SiteProvision $provisao,
        string $dominio,
        array $nomes,
        string $senhaBd,
        string $senhaAdmin,
        array $opcoes,
        string $protocolo,
    ): void {
        Credential::create([
            'client_id' => $provisao->client_id,
            'label'     => "{$dominio} — base de dados",
            'category'  => 'db',
            'url'       => 'localhost',
            'username'  => $nomes['bd_user'],
            'password'  => $senhaBd,
            'notes'     => "Base de dados {$nomes['bd']} em {$provisao->server?->host}. Criada pelo painel.",
        ]);

        if ($opcoes['wordpress'] ?? true) {
            Credential::create([
                'client_id' => $provisao->client_id,
                'label'     => "{$dominio} — WordPress",
                'category'  => 'wordpress',
                'url'       => "{$protocolo}://{$dominio}/wp-admin",
                'username'  => $opcoes['admin'] ?? 'ateneya',
                'password'  => $senhaAdmin,
                'notes'     => 'Conta de administração criada na instalação.',
            ]);
        }
    }

    // ── Motor ────────────────────────────────────────────────────────────────

    /**
     * Corre um passo, guarda o que deu e devolve a saída. Um passo obrigatório
     * que falhe pára tudo; um opcional (o SSL, quando o domínio ainda não
     * aponta para cá) fica com aviso e a receita continua.
     */
    private function passo(
        SiteProvision $provisao,
        string $chave,
        string $label,
        string $comando,
        bool $opcional = false,
        ?\Closure $validar = null,
    ): string {
        $resposta = $this->ssh->run($provisao->server, $comando, timeout: 600);

        $saida = trim((string) $resposta['output']);
        $codigo = $resposta['exit_code'];
        $estado = $codigo === 0 ? 'ok' : ($opcional ? 'aviso' : 'erro');

        $passos   = $provisao->passos ?? [];
        $passos[] = [
            'chave'  => $chave,
            'label'  => $label,
            'estado' => $estado,
            'saida'  => Str::limit($saida, 4000),
        ];

        $provisao->update([
            'passos' => $passos,
            'log'    => trim(($provisao->log ?? '')."\n\n── {$label}\n$ {$comando}\n{$saida}"),
        ]);

        if ($validar) {
            $validar($saida);
        }

        if ($codigo !== 0 && ! $opcional) {
            throw new \RuntimeException("Falhou em \"{$label}\": ".Str::limit($saida, 500));
        }

        return $saida;
    }
}
