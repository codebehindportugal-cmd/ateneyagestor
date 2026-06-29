<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\SyncProject;
use Illuminate\Database\Seeder;

/**
 * Cria os projetos de sincronizacao conhecidos.
 * Corre com: php artisan db:seed --class=SyncProjectSeeder
 *
 * Depois de correr, vai ao admin > Sincronizadores > Gerar token
 * e copia o token para o .env do respetivo script Python.
 */
class SyncProjectSeeder extends Seeder
{
    public function run(): void
    {
        $faustinoClient = Client::firstOrCreate(
            ['email' => 'faustinoclemente@example.com'],
            ['name' => 'Faustino Clemente', 'company' => 'Faustino Clemente', 'is_active' => true, 'password' => 'changeme123']
        );

        $marcoRaquelClient = Client::firstOrCreate(
            ['email' => 'marcoeraquel@example.com'],
            ['name' => 'Marco e Raquel', 'company' => 'Marco e Raquel', 'is_active' => true, 'password' => 'changeme123']
        );

        $phc = SyncProject::firstOrCreate(
            ['slug' => 'phc-woo-faustino'],
            [
                'name' => 'PHC → Faustino Clemente',
                'type' => 'phc_woo',
                'site_url' => 'https://www.faustinoclemente.pt',
                'client_id' => $faustinoClient->id,
                'host' => 'vmi2463138.contaboserver.net (servidor do cliente)',
                'runner_mode' => 'external',
                'is_active' => true,
                'notes' => 'Script phc_woo_sync. Corre no servidor do cliente. BD PHC: FClemente_16.',
            ]
        );

        $wintouch = SyncProject::firstOrCreate(
            ['slug' => 'wintouch-woo-marcoeraquel'],
            [
                'name' => 'Wintouch → Marco e Raquel',
                'type' => 'wintouch_woo',
                'site_url' => 'https://marcoeraquel.pt',
                'client_id' => $marcoRaquelClient->id,
                'host' => 'servidor empresa (local / VPS Codebehind)',
                'runner_mode' => 'local',
                'runner_script_path' => 'syncer/wintouch_woo/main.py',
                'is_active' => true,
                'notes' => 'Script wintouch_woo_sync. Sincroniza produtos, encomendas e descontos.',
            ]
        );

        $wintouch->update([
            'runner_mode' => 'local',
            'runner_script_path' => $wintouch->runner_script_path ?: 'syncer/wintouch_woo/main.py',
        ]);

        $phc->update([
            'runner_mode' => 'external',
        ]);

        $this->command?->info("SyncProject '{$phc->name}' pronto.");
        $this->command?->info("SyncProject '{$wintouch->name}' pronto.");
        $this->command?->warn('Agora vai ao admin > Sincronizadores > Gerar token para cada projeto e cola o token no .env do script correspondente (BACKUP_MANAGER_TOKEN=...).');
    }
}
