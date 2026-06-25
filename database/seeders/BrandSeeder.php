<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $ateneya = Brand::firstOrCreate(
            ['name' => 'Ateneya'],
            [
                'color'          => '#6366f1',
                'is_active'      => true,
                'parent_brand_id' => null,
            ]
        );

        Brand::firstOrCreate(
            ['name' => 'Extravaganty'],
            [
                'color'          => '#f59e0b',
                'is_active'      => true,
                'parent_brand_id' => $ateneya->id,
            ]
        );

        Brand::firstOrCreate(
            ['name' => 'Horta da Maria'],
            [
                'color'          => '#22c55e',
                'is_active'      => true,
                'parent_brand_id' => $ateneya->id,
            ]
        );
    }
}
