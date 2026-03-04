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

            // User: Standaard medewerker. Kan gebruikers en projecten bekijken
            'user' => [
                'view_users',
                'view_projects',
            ],

            // Surveyor: Inspecteur. Kan gebruikers bekijken en projecten bekijken/bewerken
            'surveyor' => [
                'view_users',
                'view_projects',
                'edit_projects',
            ],

            // Painter: Schilder. Zelfde rechten als surveyor, maar in de frontend
            // beperkte edit mogelijkheden voor specifieke project onderdelen
            'painter' => [
                'view_users',
                'view_projects',
                'edit_projects',
            ],

            // =========================================================================
            // Guest Roles (voor bezoekers van andere organisaties)
            // =========================================================================

            // Viewer: Read-only toegang. Kan alleen projecten bekijken
            'viewer' => [
                'view_projects',
            ],

            // Owner Representative: Vertegenwoordiger van de eigenaar.
            // Zelfde rechten als viewer (read-only)
            'owner representative' => [
                'view_projects',
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
