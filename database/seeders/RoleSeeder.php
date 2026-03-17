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

        $roles = [
            // =========================================================================
            // Employee Roles (voor eigen medewerkers van de organisatie)
            // =========================================================================

            // Admin: Volledige toegang tot alle features inclusief organisatie instellingen
            'admin' => [
                'type' => 'employee',
                'permissions' => $allPermissions,
            ],

            // Main User: Alles behalve organisatie instellingen en guest role configuratie
            'main user' => [
                'type' => 'employee',
                'permissions' => array_values(array_diff($allPermissions, [
                    'manage_guest_roles',
                    'manage_settings',
                ])),
            ],

            // Invitation Manager: Kan gebruikers bekijken, uitnodigingen versturen en
            // registratie aanvragen verwerken. Geen toegang tot gebruikers bewerken/verwijderen
            'invitation manager' => [
                'type' => 'employee',
                'permissions' => [
                    'view_users',
                    'view_invitations',
                    'create_invitations',
                    'manage_invitations',
                    'view_registrations',
                    'process_registrations',
                ],
            ],

            // User: Standaard medewerker. Kan alles bekijken (alle view permissions)
            'user' => [
                'type' => 'employee',
                'permissions' => $viewPermissions,
            ],

            // Surveyor: Inspecteur. Alle view permissions + volledige CRUD op project gerelateerde entities
            'surveyor' => [
                'type' => 'employee',
                'permissions' => array_merge($viewPermissions, [
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
            ],

            // =========================================================================
            // Guest Roles (voor bezoekers van andere organisaties)
            // =========================================================================

            // Viewer: Read-only toegang. Alle view permissions behalve view_users en view_shipyards
            'viewer' => [
                'type' => 'guest',
                'permissions' => array_values(array_diff($viewPermissions, ['view_users', 'view_shipyards'])),
            ],

            // Owner Representative: Zelfde rechten als viewer (read-only)
            'owner representative' => [
                'type' => 'guest',
                'permissions' => array_values(array_diff($viewPermissions, ['view_users', 'view_shipyards'])),
            ],

            // Applicator: Schilder. Alle view permissions + project edit rechten
            'applicator' => [
                'type' => 'guest',
                'permissions' => array_merge($viewPermissions, [
                    'edit_projects',
                    'download_documents',
                    'edit_decks',
                    'edit_areas',
                    'edit_stages',
                ]),
            ],

            // Werf: Zelfde rechten als applicator
            'werf' => [
                'type' => 'guest',
                'permissions' => array_merge($viewPermissions, [
                    'edit_projects',
                    'download_documents',
                    'edit_decks',
                    'edit_areas',
                    'edit_stages',
                ]),
            ],

            // Manufactor: Zelfde rechten als applicator
            'manufactor' => [
                'type' => 'guest',
                'permissions' => array_merge($viewPermissions, [
                    'edit_projects',
                    'download_documents',
                    'edit_decks',
                    'edit_areas',
                    'edit_stages',
                ]),
            ],

            // Class: Zelfde rechten als applicator
            'class' => [
                'type' => 'guest',
                'permissions' => array_merge($viewPermissions, [
                    'edit_projects',
                    'download_documents',
                    'edit_decks',
                    'edit_areas',
                    'edit_stages',
                ]),
            ],
        ];

        foreach ($roles as $roleName => $config) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
            );
            $role->update(['type' => $config['type']]);
            $role->syncPermissions($config['permissions']);
        }

        // Remove roles that are no longer defined (only if they have no users)
        $definedRoleNames = array_keys($roles);
        $rolesToDelete = Role::whereNotIn('name', $definedRoleNames)->get();

        foreach ($rolesToDelete as $role) {
            if ($role->users()->count() === 0) {
                $role->delete();
            }
        }
    }
}
