<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed landlord (central) database
        $this->call([
            SystemAdminSeeder::class,
            DefaultTenantSeeder::class,
        ]);
    }
}
