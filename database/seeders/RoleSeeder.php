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

        // Get all view permissions
        $viewPermissions = array_values(array_filter($allPermissions, fn($p) => str_starts_with($p, 'view_')));

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

            // User: Standaard medewerker. Kan alles bekijken (alle view permissions)
            'user' => $viewPermissions,

            // Surveyor: Inspecteur. Alle view permissions + volledige CRUD op project gerelateerde entities
            'surveyor' => array_merge($viewPermissions, [
                'edit_projects',
                'manage_project_members',
                'manage_project_signers',
                'create_document_types',
                'edit_document_types',
                'delete_document_types',
                'download_documents',
                'upload_documents',
                'delete_documents',
                'create_decks',
                'edit_decks',
                'delete_decks',
                'create_areas',
                'edit_areas',
                'delete_areas',
                'create_stages',
                'edit_stages',
                'delete_stages',
            ]),

            // Painter: Schilder. Alle view permissions + edit/upload rechten
            'painter' => array_merge($viewPermissions, [
                'edit_projects',
                'download_documents',
                'upload_documents',
                'edit_decks',
                'edit_areas',
                'edit_stages',
            ]),

            // =========================================================================
            // Guest Roles (voor bezoekers van andere organisaties)
            // =========================================================================

            // Viewer: Read-only toegang. Alle view permissions behalve view_users en view_shipyards
            'viewer' => array_values(array_diff($viewPermissions, ['view_users', 'view_shipyards'])),

            // Owner Representative: Zelfde rechten als viewer (read-only)
            'owner representative' => array_values(array_diff($viewPermissions, ['view_users', 'view_shipyards'])),
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }
        
    }
}
