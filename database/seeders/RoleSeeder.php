<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure permissions exist first
        $this->call(PermissionSeeder::class);

        // Get all permissions
        $allPermissions = Permission::pluck('name')->toArray();

        // Filter out restricted permissions for this tenant
        $allowedPermissions = $this->getAllowedPermissions($allPermissions);

        // Get all view permissions (filtered)
        $viewPermissions = array_values(array_filter($allowedPermissions, fn($p) => str_starts_with($p, 'view_')));

        $roles = [
            // =========================================================================
            // Employee Roles (voor eigen medewerkers van de organisatie)
            // =========================================================================

            // Admin: Volledige toegang tot alle allowed features
            'admin' => [
                'type' => 'employee',
                'permissions' => $allowedPermissions,
            ],

            // Main User: Alles behalve organisatie instellingen en guest role configuratie
            'main user' => [
                'type' => 'employee',
                'permissions' => array_values(array_diff($allowedPermissions, [
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
                ['uuid' => Str::uuid()->toString()]
            );

            // Update type and UUID if role already existed
            $updates = ['type' => $config['type']];
            if (empty($role->uuid)) {
                $updates['uuid'] = Str::uuid()->toString();
            }
            $role->update($updates);

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

    /**
     * Get allowed permissions for the current tenant.
     * Filters out any restricted permissions.
     */
    private function getAllowedPermissions(array $allPermissions): array
    {
        $tenant = tenant();

        if (!$tenant) {
            return $allPermissions;
        }

        // Get tenant from landlord database to access restricted_permissions
        $landlordTenant = Tenant::find($tenant->id);

        if (!$landlordTenant || $landlordTenant->isMainOrganization()) {
            return $allPermissions;
        }

        $restrictedPermissions = $landlordTenant->restricted_permissions ?? [];

        if (empty($restrictedPermissions)) {
            return $allPermissions;
        }

        return array_values(array_diff($allPermissions, $restrictedPermissions));
    }
}
