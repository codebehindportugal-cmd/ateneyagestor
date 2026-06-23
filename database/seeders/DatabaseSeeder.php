<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Server;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo/starter data -- safe to run on a fresh install (`php artisan
 * db:seed`) to get a feel for the admin panel before connecting it to a
 * real Pi. None of this is required for production use; delete the
 * records from the admin panel once you're adding real clients/servers.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Andre Mendes',
            'email' => 'andre.f.mendes92@gmail.com',
            'password' => 'changeme123', // hashed automatically by the 'hashed' cast -- TROCA NO PRIMEIRO LOGIN
        ]);

        $client = Client::create([
            'name' => 'Cliente Exemplo',
            'company' => 'Exemplo Lda',
            'email' => 'cliente@example.com',
            'phone' => '+351 900 000 000',
            'password' => 'changeme123', // gives this demo client portal access -- TROCA / REMOVE em produção
            'is_active' => true,
        ]);

        $agent = Agent::create([
            'name' => 'Pi de casa',
            'backup_root' => '/mnt/backup-disk',
        ]);
        $plainTextToken = $agent->createToken('agent_sync')->plainTextToken;

        Server::create([
            'client_id' => $client->id,
            'agent_id' => $agent->id,
            'name' => 'vps-acme-site',
            'type' => 'vps_laravel',
            'host' => '203.0.113.10',
            'port' => 22,
            'user' => 'deploy',
            'app_path' => '/var/www/acme-site',
            'storage_paths' => ['storage/app', 'storage/logs'],
        ]);

        Server::create([
            'client_id' => $client->id,
            'agent_id' => $agent->id,
            'name' => 'plesk-example-host',
            'type' => 'plesk',
            'host' => '198.51.100.20',
            'port' => 22,
            'user' => 'root',
            'domain' => 'example.com',
        ]);

        Server::create([
            'client_id' => $client->id,
            'agent_id' => $agent->id,
            'name' => 'cpanel-example-account',
            'type' => 'cpanel',
            'host' => 'cpanel.example.net',
            'api_port' => 2083,
            'backup_dest' => 'homedir',
        ]);

        Invoice::create([
            'client_id' => $client->id,
            'number' => 'FAT-2026-0001',
            'amount_cents' => 5000,
            'currency' => 'EUR',
            'status' => 'paid',
            'issued_at' => now()->subMonth(),
            'due_at' => now()->subMonth()->addDays(15),
            'paid_at' => now()->subMonth()->addDays(10),
            'description' => 'Manutencao mensal -- backups e suporte',
        ]);

        Invoice::create([
            'client_id' => $client->id,
            'number' => 'FAT-2026-0002',
            'amount_cents' => 5000,
            'currency' => 'EUR',
            'status' => 'issued',
            'issued_at' => now(),
            'due_at' => now()->addDays(15),
            'description' => 'Manutencao mensal -- backups e suporte',
        ]);

        $ticket = Ticket::create([
            'client_id' => $client->id,
            'subject' => 'Backup do site nao apareceu ontem',
            'status' => 'open',
            'priority' => 'normal',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'author_type' => 'client',
            'author_client_id' => $client->id,
            'body' => 'Reparei que nao ha backup de ontem para o vps-acme-site, podem confirmar?',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'author_type' => 'staff',
            'author_user_id' => $admin->id,
            'body' => 'A verificar -- obrigado pelo aviso.',
        ]);

        $this->command?->info('Seed concluido.');
        $this->command?->warn("Token do agente 'Pi de casa' (copia agora, so aparece aqui): {$plainTextToken}");
        $this->command?->warn('Login admin: andre.f.mendes92@gmail.com / changeme123 (TROCA a password)');
        $this->command?->warn('Login cliente demo: cliente@example.com / changeme123 (TROCA ou apaga este registo demo)');
    }
}
