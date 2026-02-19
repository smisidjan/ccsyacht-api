<?php

namespace Database\Seeders;

use App\Models\SystemAdmin;
use Illuminate\Database\Seeder;

class SystemAdminSeeder extends Seeder
{
    public function run(): void
    {
        SystemAdmin::firstOrCreate(
            ['email' => 'admin@ccsyacht.com'],
            [
                'name' => 'System Administrator',
                'password' => 'password',
                'active' => true,
            ]
        );
    }
}
