<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure permissions exist first
        $this->call(PermissionSeeder::class);

        // Get all permissions for admin
        $allPermissions = Permission::pluck('name')->toArray();

        $rolePermissions = [
            // =========================================================================
            // Employee Roles (voor eigen medewerkers van de organisatie)
            // =========================================================================

            // Admin: Volledige toegang tot alle features inclusief organisatie instellingen
            'admin' => $allPermissions,

            // Main User: Alles behalve organisatie instellingen en guest role configuratie
            'main user' => array_values(array_diff($allPermissions, [
                'manage_guest_roles',
                'manage_settings',
            ])),

            // Invitation Manager: Kan gebruikers bekijken, uitnodigingen versturen en
            // registratie aanvragen verwerken. Geen toegang tot gebruikers bewerken/verwijderen
            'invitation manager' => [
                'view_users',
                'view_invitations',
                'create_invitations',
                'manage_invitations',
                'view_registrations',
                'process_registrations',
            ],

            // User: Standaard medewerker. Kan gebruikers, shipyards en projecten bekijken
            'user' => [
                'view_users',
                'view_shipyards',
                'view_projects',
            ],

            // Surveyor: Inspecteur. Kan gebruikers en shipyards bekijken,
            // projecten bekijken/bewerken, project leden, document types en documenten beheren
            'surveyor' => [
                'view_users',
                'view_shipyards',
                'view_projects',
                'edit_projects',
                'manage_project_members',
                'view_document_types',
                'create_document_types',
                'edit_document_types',
                'delete_document_types',
                'view_documents',
                'download_documents',
                'upload_documents',
                'delete_documents',
            ],

            // Painter: Schilder. Kan gebruikers en shipyards bekijken,
            // projecten bekijken/bewerken, documenten bekijken, downloaden en uploaden
            'painter' => [
                'view_users',
                'view_shipyards',
                'view_projects',
                'edit_projects',
                'view_document_types',
                'view_documents',
                'download_documents',
                'upload_documents',
            ],

            // =========================================================================
            // Guest Roles (voor bezoekers van andere organisaties)
            // =========================================================================

            // Viewer: Read-only toegang. Kan shipyards, projecten, document types en documenten bekijken
            'viewer' => [
                'view_shipyards',
                'view_projects',
                'view_document_types',
                'view_documents',
            ],

            // Owner Representative: Vertegenwoordiger van de eigenaar.
            // Zelfde rechten als viewer (read-only)
            'owner representative' => [
                'view_shipyards',
                'view_projects',
                'view_document_types',
                'view_documents',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }

        // Remove deprecated yard role if exists
        Role::where('name', 'yard')->delete();
    }
}
