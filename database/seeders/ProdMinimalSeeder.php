<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * PRODUCTION-SAFE seed (03-technical-specs/13-testing-qa.md §6):
 * roles/permissions, launch locales and settings ONLY — zero fake
 * content ever reaches a production database (schema §13 demo-blocking
 * rule). UAT fixtures live exclusively in DemoSeeder (staging/local).
 */
final class ProdMinimalSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            LocalesSeeder::class,
            SettingsSeeder::class,
            CmsSeeder::class,
            ServicesSeeder::class,
            CitiesSeeder::class,
            CategoriesSeeder::class,
        ]);
    }
}
