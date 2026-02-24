<?php

namespace Database\Seeders;

use App\Models\SystemAdmin;
use Illuminate\Database\Seeder;

class SystemAdminSeeder extends Seeder
{
    public function run(): void
    {
        SystemAdmin::firstOrCreate(
            ['email' => env('SYSTEM_ADMIN_EMAIL', 'admin@ccsyacht.com')],
            [
                'name' => env('SYSTEM_ADMIN_NAME', 'System Administrator'),
                'password' => null,
                'active' => true,
            ]
        );
    }
}
