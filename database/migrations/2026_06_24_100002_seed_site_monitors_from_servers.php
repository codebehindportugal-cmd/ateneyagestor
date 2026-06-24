<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $servers = DB::table('servers')
            ->whereNotNull('domain')
            ->where('domain', '!=', '')
            ->where('is_active', 1)
            ->orderBy('id')
            ->get();

        $seenDomains = [];

        foreach ($servers as $server) {
            $domain = strtolower(trim($server->domain));

            if (isset($seenDomains[$domain])) {
                continue;
            }

            $url     = 'https://' . $domain;
            $exists  = DB::table('site_monitors')->where('url', $url)->exists();

            if ($exists) {
                $seenDomains[$domain] = true;
                continue;
            }

            DB::table('site_monitors')->insert([
                'client_id'      => $server->client_id ?? null,
                'server_id'      => $server->id,
                'name'           => $server->name,
                'url'            => $url,
                'is_active'      => true,
                'status'         => 'unknown',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $seenDomains[$domain] = true;
        }
    }

    public function down(): void
    {
        // Remove only monitors created by this migration (status unknown, no checks yet)
        DB::table('site_monitors')
            ->whereNull('last_checked_at')
            ->where('status', 'unknown')
            ->delete();
    }
};
