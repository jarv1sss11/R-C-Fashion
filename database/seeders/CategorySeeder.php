<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seeds the five top-level categories referenced by the site nav. Kids
     * and Sports were added in Phase 13.1 to match the approved catalogue
     * expansion scope (UI_BLUEPRINT.md / MASTER_BLUEPRINT.md).
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Men', 'slug' => 'men', 'display_order' => 1],
            ['name' => 'Women', 'slug' => 'women', 'display_order' => 2],
            ['name' => 'Kids', 'slug' => 'kids', 'display_order' => 3],
            ['name' => 'Sports', 'slug' => 'sports', 'display_order' => 4],
            ['name' => 'Accessories', 'slug' => 'accessories', 'display_order' => 5],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
