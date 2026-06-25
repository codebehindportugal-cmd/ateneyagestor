<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Top-level brand: Ateneya
        $ateneyaBrandId = DB::table('brands')->insertGetId([
            'name'            => 'Ateneya',
            'color'           => '#6366f1',
            'is_active'       => true,
            'parent_brand_id' => null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        // Sub-brands
        $hortaId = DB::table('brands')->insertGetId([
            'name'            => 'Horta da Maria',
            'color'           => '#22c55e',
            'is_active'       => true,
            'parent_brand_id' => $ateneyaBrandId,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        DB::table('brands')->insert([
            'name'            => 'Extravaganty',
            'color'           => '#f59e0b',
            'is_active'       => true,
            'parent_brand_id' => $ateneyaBrandId,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        // Link existing clients to their brands
        DB::table('clients')->where('name', 'Ateneya')
            ->update(['brand_id' => $ateneyaBrandId]);

        DB::table('clients')->where('name', 'Horta da Maria')
            ->update(['brand_id' => $hortaId]);
    }

    public function down(): void
    {
        DB::table('clients')->whereIn('name', ['Ateneya', 'Horta da Maria'])
            ->update(['brand_id' => null]);

        DB::table('brands')->whereIn('name', ['Ateneya', 'Horta da Maria', 'Extravaganty'])
            ->delete();
    }
};
