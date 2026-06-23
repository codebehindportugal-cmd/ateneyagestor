<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        // client_email => actual DB email from VpsServerSeeder
        $services = [
            // Codebehind Tech interno
            ['client_email' => 'interno@codebehind.pt', 'name' => 'Domínio codebehindtech.com', 'type' => 'domain', 'domain' => 'codebehindtech.com', 'billing_cycle' => 'annual', 'amount_cents' => 1200, 'renewal_date' => '2026-11-01', 'registrar' => 'Eurodns'],
            ['client_email' => 'interno@codebehind.pt', 'name' => 'Domínio codebehind.pt', 'type' => 'domain', 'domain' => 'codebehind.pt', 'billing_cycle' => 'annual', 'amount_cents' => 1000, 'renewal_date' => '2026-11-01', 'registrar' => 'Eurodns'],

            // Alorfisconta
            ['client_email' => 'info@alorfisconta.com', 'name' => 'Domínio alorfisconta.com', 'type' => 'domain', 'domain' => 'alorfisconta.com', 'billing_cycle' => 'annual', 'amount_cents' => 1200, 'renewal_date' => '2027-01-01', 'registrar' => 'Eurodns'],

            // Imonovo
            ['client_email' => 'info@imonovo.com', 'name' => 'Domínio imonovo.com', 'type' => 'domain', 'domain' => 'imonovo.com', 'billing_cycle' => 'annual', 'amount_cents' => 1200, 'renewal_date' => '2027-03-01', 'registrar' => 'Eurodns'],

            // Terras de Viriarte
            ['client_email' => 'info@terrasdviriarte.com', 'name' => 'Domínio terrasdviriarte.com', 'type' => 'domain', 'domain' => 'terrasdviriarte.com', 'billing_cycle' => 'annual', 'amount_cents' => 1200, 'renewal_date' => '2027-06-01', 'registrar' => 'Eurodns'],

            // Sagovit
            ['client_email' => 'info@sagovit.com', 'name' => 'Domínio sagovit.pt', 'type' => 'domain', 'domain' => 'sagovit.pt', 'billing_cycle' => 'annual', 'amount_cents' => 1000, 'renewal_date' => '2027-02-01', 'registrar' => 'Eurodns'],

            // Horácio V. Leal
            ['client_email' => 'info@horaciovleal.com', 'name' => 'Domínio horaciovleal.com', 'type' => 'domain', 'domain' => 'horaciovleal.com', 'billing_cycle' => 'annual', 'amount_cents' => 1200, 'renewal_date' => '2027-04-01', 'registrar' => 'Eurodns'],

            // JAC Faria
            ['client_email' => 'info@jacfaria.com', 'name' => 'Domínio jacfaria.com', 'type' => 'domain', 'domain' => 'jacfaria.com', 'billing_cycle' => 'annual', 'amount_cents' => 1200, 'renewal_date' => '2027-05-01', 'registrar' => 'Eurodns'],

            // Fruta Alvor
            ['client_email' => 'info@frutaalvor.com', 'name' => 'Domínio frutaalvor.com', 'type' => 'domain', 'domain' => 'frutaalvor.com', 'billing_cycle' => 'annual', 'amount_cents' => 1200, 'renewal_date' => '2027-08-01', 'registrar' => 'Eurodns'],

            // Brital Flor
            ['client_email' => 'info@britalflor.com', 'name' => 'Domínio britalflor.com', 'type' => 'domain', 'domain' => 'britalflor.com', 'billing_cycle' => 'annual', 'amount_cents' => 1200, 'renewal_date' => '2027-09-01', 'registrar' => 'Eurodns'],

            // Horta da Maria
            ['client_email' => 'info@hortadamaria.com', 'name' => 'Domínio hortadamaria.com', 'type' => 'domain', 'domain' => 'hortadamaria.com', 'billing_cycle' => 'annual', 'amount_cents' => 1200, 'renewal_date' => '2027-07-01', 'registrar' => 'Eurodns'],
        ];

        foreach ($services as $data) {
            $client = Client::where('email', $data['client_email'])->first();
            if (! $client) {
                continue;
            }

            Service::firstOrCreate(
                ['client_id' => $client->id, 'name' => $data['name']],
                [
                    'type'          => $data['type'],
                    'domain'        => $data['domain'] ?? null,
                    'billing_cycle' => $data['billing_cycle'],
                    'amount_cents'  => $data['amount_cents'],
                    'renewal_date'  => $data['renewal_date'],
                    'registrar'     => $data['registrar'] ?? null,
                    'notes'         => $data['notes'] ?? null,
                    'auto_renew'    => true,
                    'is_active'     => true,
                ]
            );
        }
    }
}
