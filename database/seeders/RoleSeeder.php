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
            // projecten bekijken/bewerken, project leden, document types, documenten,
            // decks, areas en stages volledig beheren
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
                'view_decks',
                'create_decks',
                'edit_decks',
                'delete_decks',
                'view_areas',
                'create_areas',
                'edit_areas',
                'delete_areas',
                'view_stages',
                'create_stages',
                'edit_stages',
                'delete_stages',
            ],

            // Painter: Schilder. Kan gebruikers en shipyards bekijken,
            // projecten bekijken/bewerken, documenten bekijken/downloaden/uploaden,
            // decks/areas/stages bekijken en bewerken
            'painter' => [
                'view_users',
                'view_shipyards',
                'view_projects',
                'edit_projects',
                'view_document_types',
                'view_documents',
                'download_documents',
                'upload_documents',
                'view_decks',
                'edit_decks',
                'view_areas',
                'edit_areas',
                'view_stages',
                'edit_stages',
            ],

            // =========================================================================
            // Guest Roles (voor bezoekers van andere organisaties)
            // =========================================================================

            // Viewer: Read-only toegang. Kan shipyards, projecten, document types,
            // documenten, decks, areas en stages bekijken
            'viewer' => [
                'view_shipyards',
                'view_projects',
                'view_document_types',
                'view_documents',
                'view_decks',
                'view_areas',
                'view_stages',
            ],

            // Owner Representative: Vertegenwoordiger van de eigenaar.
            // Zelfde rechten als viewer (read-only)
            'owner representative' => [
                'view_shipyards',
                'view_projects',
                'view_document_types',
                'view_documents',
                'view_decks',
                'view_areas',
                'view_stages',
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
