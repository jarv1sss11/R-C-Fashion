<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Standard demo credentials used throughout the project's QA reports and
 * documentation (admin@example.com / beatrice@example.com, both
 * password123). Previously created by DemoCatalogueSeeder, which Phase 13.1
 * replaced with ProductCatalogueSeeder — that seeder's vendor/reviewer
 * accounts don't cover the admin role or the specific buyer identity these
 * credentials refer to, so this seeder keeps them intact independently of
 * the catalogue data.
 */
class DemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Amina Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'beatrice@example.com'],
            [
                'name' => 'Beatrice Buyer',
                'password' => Hash::make('password123'),
                'role' => 'buyer',
                'status' => 'active',
            ]
        );

        $this->command?->info('Demo accounts ensured: admin@example.com, beatrice@example.com.');
    }
}
