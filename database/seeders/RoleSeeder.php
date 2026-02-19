<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin' => 'Administrator with full access to all features',
            'main user' => 'Main user with elevated privileges',
            'invitation manager' => 'Can manage invitations and registration requests',
            'user' => 'Standard user with basic access',
            'yard' => 'Yard personnel',
            'surveyor' => 'Surveyor for inspections',
            'painter' => 'Painter for yacht maintenance',
            'owner representative' => 'Representative of the yacht owner',
        ];

        foreach ($roles as $name => $description) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
