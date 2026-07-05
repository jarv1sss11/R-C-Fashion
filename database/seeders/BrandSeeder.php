<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

/**
 * Realistic fictional brands spanning all five catalogue categories (Phase
 * 13.1 approved decision: a dedicated Brands table, not a plain string
 * column). Not all products carry the platform's own "R&C Signature" brand —
 * a real marketplace has many vendors/labels, which is also why the
 * recommendation engine's brand-affinity scoring has signal to work with.
 */
class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Northfield & Co', 'description' => 'Classic menswear staples built for everyday wear.'],
            ['name' => 'Marlow & Finch', 'description' => 'Smart-casual and formal menswear with a tailored edge.'],
            ['name' => 'Verona Row', 'description' => 'Contemporary womenswear for the modern city wardrobe.'],
            ['name' => 'Luma Studio', 'description' => 'Minimalist, elegant womenswear in considered fabrics.'],
            ['name' => 'Kessler Denim', 'description' => 'Denim specialists crafting durable, timeless fits for everyone.'],
            ['name' => 'Bright Sprout', 'description' => 'Playful, comfortable kidswear for little adventurers.'],
            ['name' => 'Petal & Pine', 'description' => 'Soft, seasonal essentials for babies and young children.'],
            ['name' => 'Solstice Active', 'description' => 'Performance training gear for everyday athletes.'],
            ['name' => 'Ridgeline Gear', 'description' => 'Outdoor and trail-ready sportswear built to last.'],
            ['name' => 'Atlas Trail', 'description' => 'Technical fabrics for running, hiking, and cross-training.'],
            ['name' => 'Havenwood', 'description' => 'Leather goods and everyday accessories, handcrafted details.'],
            ['name' => 'Auric & Co', 'description' => 'Fine jewellery and premium accessories for every occasion.'],
            ['name' => 'R&C Signature', 'description' => 'The house label — R&C Fashion\'s own take on seasonal staples.'],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($brand['name'])],
                ['name' => $brand['name'], 'description' => $brand['description']]
            );
        }

        $this->command?->info('Brands seeded: '.count($brands));
    }
}
