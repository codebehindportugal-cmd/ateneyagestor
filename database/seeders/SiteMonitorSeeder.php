<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\SiteMonitor;
use Illuminate\Database\Seeder;

class SiteMonitorSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::whereNotNull('website')->where('website', '!=', '')->get();

        foreach ($clients as $client) {
            SiteMonitor::firstOrCreate(
                ['url' => $client->website],
                [
                    'client_id' => $client->id,
                    'name'      => $client->name,
                    'is_active' => true,
                    'status'    => 'unknown',
                ]
            );
        }

        // Plesk panels
        $pleskPanels = [
            ['name' => 'Plesk Contabo D',       'url' => 'https://45.10.154.155:8443'],
            ['name' => 'Plesk Contabo C',       'url' => 'https://185.213.25.187:8443'],
            ['name' => 'Plesk Contabo A',       'url' => 'https://89.117.58.229:8443'],
            ['name' => 'Plesk Contabo B',       'url' => 'https://89.117.58.85:8443'],
            ['name' => 'Plesk Horta da Maria',  'url' => 'https://207.180.216.208:8443'],
            ['name' => 'Plesk Dev',             'url' => 'https://161.97.124.152:8443'],
            ['name' => 'Plesk Brital Flor',     'url' => 'https://158.220.105.131:8443'],
            ['name' => 'Plesk Loja Amster',     'url' => 'https://89.117.60.53:8443'],
        ];

        foreach ($pleskPanels as $panel) {
            SiteMonitor::firstOrCreate(
                ['url' => $panel['url']],
                [
                    'client_id' => null,
                    'name'      => $panel['name'],
                    'is_active' => true,
                    'status'    => 'unknown',
                ]
            );
        }
    }
}
