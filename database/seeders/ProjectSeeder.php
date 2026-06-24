<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        // --- lookup helpers (por domain/name, não por ID hardcoded) ---
        $server = fn (string $domain) => Server::where('domain', $domain)->value('id');
        $client = fn (string $email)  => Client::where('email', $email)->value('id');

        // Garantir que existe um servidor para o Backup Manager (gestao.ateneya.com)
        $backupManagerServer = Server::firstOrCreate(
            ['name' => 'gestao-ateneya-com'],
            [
                'host'      => '144.91.100.40',
                'domain'    => 'gestao.ateneya.com',
                'type'      => 'plesk',
                'port'      => 22,
                'user'      => 'root',
                'is_active' => true,
            ]
        );

        // Clientes sem servidor (novos projectos) — criar se não existirem
        $assocSantana = Client::firstOrCreate(
            ['email' => 'info@associacaosantana.pt'],
            ['name' => 'Associação Santana', 'company' => 'Associação Santana', 'is_active' => true, 'password' => 'changeme123']
        );
        $mordFocas = Client::firstOrCreate(
            ['email' => 'info@mordfocas.pt'],
            ['name' => 'Mord Focas', 'company' => 'Mord Focas', 'is_active' => true, 'password' => 'changeme123']
        );
        $frutasLegumesCasa = Client::firstOrCreate(
            ['email' => 'info@frutaseleguemesemcasa.pt'],
            ['name' => 'Frutas e Legumes em Casa', 'company' => 'Frutas e Legumes em Casa', 'is_active' => true, 'password' => 'changeme123']
        );

        // Cliente Raquel (Marco e Raquel)
        $raquel = Client::where('email', 'marcoraquel.lda@gmail.com')->first();

        $projects = [
            // ── Internos ─────────────────────────────────────────────────────────
            [
                'name'        => 'Horta da Maria',
                'slug'        => 'horta-da-maria',
                'is_internal' => true,
                'client_id'   => $client('info@hortadamaria.com'),
                'server_id'   => $server('gestao.hortadamaria.com'),
                'type'        => 'laravel',
                'status'      => 'active',
                'url'         => 'https://gestao.hortadamaria.com',
                'notes'       => 'Gestão agrícola — Laravel + Inertia, servidor Horta/Plesk (161.97.124.152)',
            ],
            [
                'name'        => 'Ateneya',
                'slug'        => 'ateneya',
                'is_internal' => true,
                'client_id'   => $client('info@ateneya.com'),
                'server_id'   => $server('ateneya.com'),
                'type'        => 'wordpress',
                'status'      => 'active',
                'url'         => 'https://ateneya.com',
                'notes'       => 'Site institucional WordPress, servidor Liberne (89.117.60.53)',
            ],
            [
                'name'        => 'Frutas e Legumes em Casa',
                'slug'        => 'frutas-legumes-casa',
                'is_internal' => true,
                'client_id'   => $frutasLegumesCasa->id,
                'server_id'   => null,
                'type'        => 'woocommerce',
                'status'      => 'development',
                'url'         => null,
                'notes'       => 'Servidor a confirmar.',
            ],
            [
                'name'        => 'Backup Manager',
                'slug'        => 'backup-manager',
                'is_internal' => true,
                'client_id'   => $client('interno@codebehind.pt'),
                'server_id'   => $backupManagerServer->id,
                'type'        => 'laravel',
                'status'      => 'active',
                'url'         => 'https://gestao.ateneya.com',
                'notes'       => 'O próprio sistema de gestão — Laravel + Filament, servidor Desenvolvimento/Plesk (144.91.100.40)',
            ],

            // ── Clientes ─────────────────────────────────────────────────────────
            [
                'name'        => 'Marco e Raquel',
                'slug'        => 'marco-e-raquel',
                'is_internal' => false,
                'client_id'   => $raquel?->id,
                'server_id'   => null,
                'type'        => 'sync',
                'status'      => 'active',
                'url'         => null,
                'notes'       => 'Sincronizador WinTouch → WooCommerce',
            ],
            [
                'name'        => 'Associação Santana',
                'slug'        => 'associacao-santana',
                'is_internal' => false,
                'client_id'   => $assocSantana->id,
                'server_id'   => null,
                'type'        => 'wordpress',
                'status'      => 'active',
                'url'         => null,
                'notes'       => null,
            ],
            [
                'name'        => 'Mord Focas',
                'slug'        => 'mord-focas',
                'is_internal' => false,
                'client_id'   => $mordFocas->id,
                'server_id'   => null,
                'type'        => 'wordpress',
                'status'      => 'active',
                'url'         => null,
                'notes'       => null,
            ],
            [
                'name'        => 'Fruta Para Empresas',
                'slug'        => 'fruta-para-empresas',
                'is_internal' => false,
                'client_id'   => $client('info@frutasparaempresas.com'),
                'server_id'   => $server('frutasparaempresas.com'),
                'type'        => 'woocommerce',
                'status'      => 'active',
                'url'         => 'https://frutasparaempresas.com',
                'notes'       => null,
            ],
            [
                'name'        => 'Loja Amster',
                'slug'        => 'loja-amster',
                'is_internal' => false,
                'client_id'   => $client('info@amster.pt'),
                'server_id'   => $server('lojaamster.com'),
                'type'        => 'woocommerce',
                'status'      => 'active',
                'url'         => 'https://lojaamster.com',
                'notes'       => null,
            ],
            [
                'name'        => 'Clínica dos Anjos',
                'slug'        => 'clinica-dos-anjos',
                'is_internal' => false,
                'client_id'   => $client('info@clinicadosanjos.com'),
                'server_id'   => $server('clinicadosanjos.com'),
                'type'        => 'wordpress',
                'status'      => 'active',
                'url'         => 'https://clinicadosanjos.pt',
                'notes'       => null,
            ],
            [
                'name'        => 'Terras de Viriarte',
                'slug'        => 'terras-de-viriarte',
                'is_internal' => false,
                'client_id'   => $client('info@terrasdviriarte.com'),
                'server_id'   => $server('terrasdviriarte.com'),
                'type'        => 'wordpress',
                'status'      => 'active',
                'url'         => 'https://terrasdeviriarte.com',
                'notes'       => null,
            ],
            [
                'name'        => 'Alvor Fisconta',
                'slug'        => 'alvor-fisconta',
                'is_internal' => false,
                'client_id'   => $client('info@alorfisconta.com'),
                'server_id'   => $server('alorfisconta.com'),
                'type'        => 'wordpress',
                'status'      => 'active',
                'url'         => 'https://alvorfisconta.pt',
                'notes'       => 'Domínio .pt; servidor mapeado em alorfisconta.com',
            ],
            [
                'name'        => 'Frutalvor',
                'slug'        => 'frutalvor',
                'is_internal' => false,
                'client_id'   => $client('info@frutaalvor.com'),
                'server_id'   => $server('frutalvor.com'),
                'type'        => 'woocommerce',
                'status'      => 'active',
                'url'         => 'https://frutalvor.com',
                'notes'       => null,
            ],
            [
                'name'        => 'Imonovo',
                'slug'        => 'imonovo',
                'is_internal' => false,
                'client_id'   => $client('info@imonovo.com'),
                'server_id'   => $server('imonovo.com'),
                'type'        => 'wordpress',
                'status'      => 'active',
                'url'         => 'https://imonovo.com',
                'notes'       => null,
            ],
            [
                'name'        => 'Sagovit',
                'slug'        => 'sagovit',
                'is_internal' => false,
                'client_id'   => $client('info@sagovit.com'),
                'server_id'   => $server('sagovit.com'),
                'type'        => 'wordpress',
                'status'      => 'active',
                'url'         => 'https://sagovit.com',
                'notes'       => null,
            ],
            [
                'name'        => 'Codebehind Tech',
                'slug'        => 'codebehind-tech',
                'is_internal' => false,
                'client_id'   => $client('interno@codebehind.pt'),
                'server_id'   => $server('codebehindtech.com'),
                'type'        => 'wordpress',
                'status'      => 'active',
                'url'         => 'https://codebehindtech.com',
                'notes'       => null,
            ],
            [
                'name'        => 'Taxi Codebehind',
                'slug'        => 'taxi-codebehind',
                'is_internal' => false,
                'client_id'   => $client('interno@codebehind.pt'),
                'server_id'   => $server('taxi.codebehind.pt'),
                'type'        => 'wordpress',
                'status'      => 'active',
                'url'         => 'https://taxi.codebehind.pt',
                'notes'       => null,
            ],
        ];

        $created = 0;
        foreach ($projects as $data) {
            Project::firstOrCreate(['slug' => $data['slug']], $data);
            $created++;
        }

        $this->command?->info("{$created} projectos adicionados/verificados.");
    }
}
