<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Root seeder — what `php artisan db:seed` / `migrate --seed` run.
 *
 * The project's doctrine (03-database-schema §13 demo-blocking rule):
 * PRODUCTION gets ProdMinimalSeeder only (roles, locales, settings,
 * CMS/services/cities/categories taxonomy — zero fake content);
 * DemoSeeder is production-forbidden and carries the local/UAT
 * fixtures (demo org, test users) on top of the prod-minimal base.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->call(ProdMinimalSeeder::class);

            return;
        }

        $this->call(DemoSeeder::class);
    }
}
