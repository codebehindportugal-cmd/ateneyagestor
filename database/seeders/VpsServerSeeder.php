<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Server;
use Illuminate\Database\Seeder;

/**
 * Popula os servidores VPS geridos. Corre com:
 *   php artisan db:seed --class=VpsServerSeeder
 *
 * Os clientes sao criados por email unico -- se ja existirem (do
 * DatabaseSeeder) sao reaproveitados. Ajusta os dados via painel admin.
 */
class VpsServerSeeder extends Seeder
{
    public function run(): void
    {
        // Helper
        $client = fn (string $name, string $email) => Client::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'company' => $name, 'is_active' => true, 'password' => 'changeme123']
        );

        // --- Clientes ---
        $codebehind   = $client('Codebehind Tech (interno)', 'interno@codebehind.pt');
        $alorfisconta = $client('Alorfisconta', 'info@alorfisconta.com');
        $imonovo      = $client('Imonovo', 'info@imonovo.com');
        $viriarte     = $client('Terras de Viriarte', 'info@terrasdviriarte.com');
        $sagovit      = $client('Sagovit', 'info@sagovit.com');
        $clinica      = $client('Clínica dos Anjos', 'info@clinicadosanjos.com');
        $ateneya      = $client('Ateneya', 'info@ateneya.com');
        $jacfaria     = $client('Jacfaria', 'info@jacfaria.com');
        $horacioleal  = $client('Horácio V. Leal', 'info@horaciovleal.com');
        $britalflor   = $client('Brital Flor', 'info@britalflor.com');
        $frutaalvor   = $client('Fruta Alvor', 'info@frutaalvor.com');
        $hortamaria   = $client('Horta da Maria', 'info@hortadamaria.com');
        $faustino     = Client::firstOrCreate(
            ['email' => 'faustinoclemente@example.com'],
            ['name' => 'Faustino Clemente', 'company' => 'Faustino Clemente', 'is_active' => true, 'password' => 'changeme123']
        );
        $amster           = $client('Loja Amster', 'info@amster.pt');
        $frutasparaempresas = $client('Fruta Para Empresas', 'info@frutasparaempresas.com');

        $servers = [
            // Contabo D — 45.10.154.155
            // Laravel app em /var/www/taxicloudtecVF1 — domínio taxis.codebehind.pt
            ['client' => $codebehind, 'name' => 'taxi-codebehind-pt',   'host' => '45.10.154.155', 'domain' => 'taxis.codebehind.pt', 'type' => 'vps_laravel', 'app_path' => '/var/www/taxicloudtecVF1'],
            ['client' => $alorfisconta,'name' => 'alorfisconta-com',     'host' => '45.10.154.155', 'domain' => 'alorfisconta.com'],

            // Contabo C — 185.213.25.187
            ['client' => $codebehind, 'name' => 'codebehindtech-com',   'host' => '185.213.25.187', 'domain' => 'codebehindtech.com'],
            ['client' => $imonovo,    'name' => 'imonovo-com',           'host' => '185.213.25.187', 'domain' => 'imonovo.com'],
            ['client' => $viriarte,   'name' => 'terrasdviriarte-com',   'host' => '185.213.25.187', 'domain' => 'terrasdviriarte.com'],

            // Contabo A — 89.117.58.229
            ['client' => $sagovit,    'name' => 'sagovit',               'host' => '89.117.58.229',  'domain' => 'sagovit.com'],
            ['client' => $clinica,    'name' => 'clinicadosanjos-com',   'host' => '89.117.58.229',  'domain' => 'clinicadosanjos.com'],
            ['client' => $ateneya,    'name' => 'ateneya',               'host' => '89.117.58.229',  'domain' => 'ateneya.com'],

            // Contabo B — 89.117.58.85
            ['client' => $jacfaria,   'name' => 'jacfaria-com',          'host' => '89.117.58.85',   'domain' => 'jacfaria.com'],
            ['client' => $horacioleal,'name' => 'horaciovleal-com',      'host' => '89.117.58.85',   'domain' => 'horaciovleal.com'],
            ['client' => $britalflor, 'name' => 'britalflor-com',        'host' => '89.117.58.85',   'domain' => 'britalflor.com'],
            ['client' => $frutaalvor, 'name' => 'frutaalvor-com',        'host' => '89.117.58.85',   'domain' => 'frutalvor.com'],

            // Horta da Maria — Plesk próprio
            ['client' => $hortamaria, 'name' => 'hortadamaria-main',     'host' => '207.180.216.208', 'domain' => 'hortadamaria.com'],
            ['client' => $hortamaria, 'name' => 'gestao-hortadamaria',   'host' => '161.97.124.152',  'domain' => 'gestao.hortadamaria.com'],

            // Dev Faustino Clemente
            ['client' => $faustino,   'name' => 'faustino-dev-plesk',    'host' => 'vmi2463138.contaboserver.net', 'domain' => 'faustinoclemente.pt'],

            // Brital Flor servidor separado
            ['client' => $britalflor, 'name' => 'britalflor-vps2',       'host' => '158.220.105.131', 'domain' => 'britalflor.com'],

            // Loja Amster
            ['client' => $amster,     'name' => 'loja-amster',           'host' => '89.117.60.53',   'domain' => 'lojaamster.com'],

            // Contabo A — 89.117.58.229
            ['client' => $frutasparaempresas, 'name' => 'frutasparaempresas-com', 'host' => '89.117.58.229', 'domain' => 'frutasparaempresas.com'],
        ];

        foreach ($servers as $s) {
            Server::firstOrCreate(
                ['name' => $s['name']],
                array_filter([
                    'client_id'     => $s['client']->id,
                    'type'          => $s['type'] ?? 'plesk',
                    'host'          => $s['host'],
                    'port'          => 22,
                    'user'          => 'root',
                    'domain'        => $s['domain'],
                    'app_path'      => $s['app_path'] ?? null,
                    'storage_paths' => $s['storage_paths'] ?? null,
                    'is_active'     => true,
                ], fn ($v) => ! is_null($v))
            );
        }

        $this->command?->info(count($servers).' servidores VPS adicionados/verificados.');
        $this->command?->warn('Revê as passwords dos clientes e associa os agentes Pi (agent_id) via painel admin.');
    }
}
