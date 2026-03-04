<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Users
            'view_users',
            'edit_users',
            'delete_users',

            // Invitations
            'view_invitations',
            'create_invitations',
            'manage_invitations',

            // Registration Requests
            'view_registrations',
            'process_registrations',

            // Organization Settings
            'manage_guest_roles',
            'manage_settings',

            // Projects (toekomstig)
            'view_projects',
            'edit_projects',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
