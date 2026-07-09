<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order is load-bearing, not arbitrary:
     *  - RiderSeeder has no dependencies on catalogue data, so it can sit
     *    anywhere; grouped here with the other account-style seeders.
     *  - CatalogueExpansionSeeder looks up categories (CategorySeeder),
     *    brands (BrandSeeder), vendor users and reviewer accounts
     *    (ProductCatalogueSeeder) by name/slug via firstOrFail() — it must
     *    run after all three or it throws.
     *  - DemoPersonaSeeder builds each persona's interaction history from
     *    CatalogueExpansionSeeder's four clusters (queried by category+
     *    brand+style) plus the wider catalogue — it must run last.
     *
     * products:backfill-type and search:rebuild-index are run afterwards,
     * not as separate seeders, because they're not seed *data* — they're
     * derived columns (product_type, search_index) computed from whatever
     * products already exist, exactly like a migration's data-fix step.
     * Both are idempotent and safe to call unconditionally.
     *
     * recommendations:bump-cache is deliberately NOT called here: migrate
     * :fresh drops and recreates the `cache` table, so there are zero
     * cached recommendation entries on a brand-new database — bumping the
     * version would be a no-op. That command only matters after a *later*
     * change to recommendation scoring/gating logic on an already-running,
     * already-cached system (see BumpRecommendationCache's doc comment).
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            BrandSeeder::class,
            DemoAccountsSeeder::class,
            RiderSeeder::class,
            ProductCatalogueSeeder::class,
            CatalogueExpansionSeeder::class,
            DemoPersonaSeeder::class,
        ]);

        Artisan::call('products:backfill-type');
        $this->command?->info(trim(Artisan::output()));

        Artisan::call('search:rebuild-index');
        $this->command?->info(trim(Artisan::output()));
    }
}
