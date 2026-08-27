<?php
/**
 * Correção dos registos de servidores no painel gestao.ateneya.com.
 *
 * - 11 registos passam de 'plesk' (errado) para 'wordpress' com o wp_root real,
 *   descoberto por SSH em cada VPS.
 * - 2 registos duplicados/migrados ficam inativos (não são apagados: o histórico
 *   de BackupRun continua ligado a eles).
 * - Desliga o cron de backup do próprio painel, que é o que gera as falhas das 03:00
 *   agora que quem faz os backups é o agente no Proxmox.
 *
 * Idempotente: pode correr duas vezes sem estragar nada.
 */

$base = '/var/www/vhosts/gestao.ateneya.com/httpdocs';
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Server;
use App\Models\Setting;

$map = [
    'clinicadosanjos-com'    => '/var/www/clinicadosanjos.pt/public_html',
    'ateneya'                => '/var/www/ateneya.com',
    'frutasparaempresas-com' => '/var/www/frutasparaempresas.com/public_html',
    'jacfaria-com'           => '/var/www/jacfaria.com/public_html',
    'horaciovleal-com'       => '/var/www/horaciovleal.com/public_html',
    'frutaalvor-com'         => '/var/www/frutalvor.com/public_html',
    'faustinoclemente-pt'    => '/var/www/faustinoclemente.pt/public_html',
    'codebehindtech-com'     => '/var/www/codebehindtech.com/public_html',
    'terrasdviriarte-com'    => '/var/www/terrasdeviriarte.com/public_html',
    'alorfisconta-com'       => '/var/www/alvorfisconta.pt/public_html',
    'loja-amster'            => '/var/www/lojaamster.com/public_html',
];

echo "== tipo + wp_root ==\n";
foreach ($map as $name => $root) {
    $server = Server::where('name', $name)->first();

    if (! $server) {
        echo sprintf("AUSENTE  %-24s (nenhum registo com este nome)\n", $name);
        continue;
    }

    $server->type = 'wordpress';
    $server->wp_root = $root;
    if (! $server->user) {
        $server->user = 'root';
    }
    $server->save();

    echo sprintf("OK       %-24s %s\n", $name, $root);
}

echo "\n== desativar ==\n";
$off = [
    'britalflor-com' => 'migrou para a VPS do cliente (fica britalflor-vps2)',
    'faustino plesk' => 'duplicado de faustinoclemente-pt',
];
foreach ($off as $name => $porque) {
    $server = Server::where('name', $name)->first();

    if (! $server) {
        echo sprintf("AUSENTE  %-24s\n", $name);
        continue;
    }

    $server->is_active = false;
    $server->save();

    echo sprintf("OFF      %-24s %s\n", $name, $porque);
}

echo "\n== sites sem registo ==\n";
/*
 * Sites que existem nos servidores mas não tinham registo nenhum no painel,
 * logo nunca eram copiados. Todos vivem em servidores com Plesk, por isso o
 * backup é por domínio (pleskbackup), não por wp_root.
 */
$novos = [
    [
        'name'    => 'hortadamaria-com',
        'host'    => '161.97.124.152',
        'domain'  => 'hortadamaria.com',
        'cliente' => 'Horta da Maria',
    ],
    [
        'name'    => 'agro-codebehind-pt',
        'host'    => '144.91.100.40',
        'domain'  => 'agro.codebehind.pt',
        'cliente' => 'Codebehind Tech (interno)',
    ],
    [
        'name'    => 'ardcsantana-ateneya-com',
        'host'    => '144.91.100.40',
        'domain'  => 'ardcsantana.ateneya.com',
        'cliente' => 'Ateneya',
    ],
    [
        'name'    => 'jornadascinegeticas-pt',
        'host'    => '144.91.100.40',
        'domain'  => 'jornadascinegeticas.pt',
        'cliente' => 'Jornadas Cinegéticas',
    ],
];

foreach ($novos as $novo) {
    if (Server::where('name', $novo['name'])->exists()) {
        echo sprintf("JA EXISTE %-24s\n", $novo['name']);
        continue;
    }

    try {
        $cliente = App\Models\Client::firstOrCreate(['name' => $novo['cliente']]);

        Server::create([
            'client_id'   => $cliente->id,
            'name'        => $novo['name'],
            'type'        => 'plesk',
            'environment' => 'production',
            'host'        => $novo['host'],
            'port'        => 22,
            'user'        => 'root',
            'domain'      => $novo['domain'],
            'is_active'   => true,
        ]);

        echo sprintf("CRIADO   %-24s %s @ %s (%s)\n", $novo['name'], $novo['domain'], $novo['host'], $cliente->name);
    } catch (\Throwable $e) {
        // Falhar a criar um não pode impedir os restantes — normalmente é um
        // campo obrigatório do modelo Client que este script não conhece.
        echo sprintf("ERRO     %-24s %s\n", $novo['name'], $e->getMessage());
    }
}

echo "\n== cron do painel ==\n";
Setting::set('cron.backup.enabled', '0');
echo 'cron.backup.enabled = ' . var_export(Setting::bool('cron.backup.enabled', true), true) . "\n";

echo "\n== resultado ==\n";
foreach (Server::where('is_active', true)->orderBy('type')->orderBy('name')->get() as $server) {
    echo sprintf(
        "%-24s %-12s %-16s %s\n",
        $server->name,
        $server->type->value,
        $server->host,
        $server->wp_root ?: ($server->domain ?: $server->app_path)
    );
}
echo "\nativos: " . Server::where('is_active', true)->count() . "\n";
