<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Every seeder here is idempotent.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            FoundationSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
