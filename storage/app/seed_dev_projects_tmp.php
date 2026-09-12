<?php

$key = '/var/www/vhosts/gestao.ateneya.com/.ssh/ateneya_vps_key';
$projects = [
    ['client' => 'Amoreirinha', 'email' => 'dev+amoreirinha@ateneya.com', 'name' => 'amoreirinha-dev', 'domain' => 'jolly-proskuriakova.144-91-100-40.plesk.page'],
    ['client' => 'Tenpol', 'email' => 'dev+tenpol@ateneya.com', 'name' => 'tenpol-dev', 'domain' => 'upbeat-mcclintock.144-91-100-40.plesk.page'],
    ['client' => 'A.S.Conect', 'email' => 'dev+asconect@ateneya.com', 'name' => 'asconect-dev', 'domain' => 'clever-clarke.144-91-100-40.plesk.page'],
];

foreach ($projects as $p) {
    $client = App\Models\Client::firstOrCreate(
        ['email' => $p['email']],
        ['name' => $p['client'], 'company' => $p['client'], 'is_active' => true]
    );

    $server = App\Models\Server::updateOrCreate(
        ['name' => $p['name']],
        [
            'client_id' => $client->id,
            'type' => 'wordpress',
            'is_active' => false,
            'environment' => 'development',
            'host' => '144.91.100.40',
            'port' => 22,
            'user' => 'root',
            'domain' => $p['domain'],
            'wp_root' => '/var/www/vhosts/' . $p['domain'] . '/httpdocs',
            'ssh_key_path' => $key,
            'notes' => 'Projeto em desenvolvimento no VPS dev (144.91.100.40). Dominio temporario Plesk -- ainda nao esta no dominio final.',
        ]
    );

    $project = App\Models\Project::updateOrCreate(
        ['slug' => \Illuminate\Support\Str::slug($p['client'])],
        [
            'name' => $p['client'],
            'is_internal' => false,
            'client_id' => $client->id,
            'server_id' => $server->id,
            'type' => 'wordpress',
            'status' => 'development',
            'url' => 'https://' . $p['domain'] . '/',
            'notes' => 'Site em desenvolvimento no VPS 144.91.100.40, dominio temporario Plesk -- ainda nao esta no dominio final.',
        ]
    );

    echo $p['client'] . ' -> client_id=' . $client->id . ' server_id=' . $server->id . ' project_id=' . $project->id . PHP_EOL;
}
