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

            // Shipyards
            'view_shipyards',
            'create_shipyards',
            'edit_shipyards',
            'delete_shipyards',

            // Projects
            'view_projects',
            'create_projects',
            'edit_projects',
            'delete_projects',
            'manage_project_members',

            // Document Types
            'view_document_types',
            'create_document_types',
            'edit_document_types',
            'delete_document_types',

            // Documents
            'view_documents',
            'download_documents',
            'upload_documents',
            'delete_documents',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
