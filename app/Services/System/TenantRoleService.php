<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TenantRoleService
{
    /**
     * List all roles in the current tenant.
     */
    public function list(): Collection
    {
        return Role::with('permissions:id,name')
            ->select(['id', 'uuid', 'name', 'type', 'guard_name'])
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get a single role by UUID.
     */
    public function find(string $uuid): Role
    {
        return Role::with('permissions:id,name')->where('uuid', $uuid)->firstOrFail();
    }

    /**
     * Create a new role.
     */
    public function create(string $name, string $type, array $permissions): Role
    {
        $role = Role::create([
            'uuid' => Str::uuid()->toString(),
            'name' => $name,
            'type' => $type,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissions);

        return $role->load('permissions:id,name');
    }

    /**
     * Update a role.
     */
    public function update(Role $role, array $data): Role
    {
        if (isset($data['name'])) {
            $role->name = $data['name'];
        }
        if (isset($data['type'])) {
            $role->type = $data['type'];
        }
        $role->save();

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role->fresh('permissions:id,name');
    }

    /**
     * Delete a role.
     *
     * @throws InvalidArgumentException if role has users assigned
     */
    public function delete(Role $role): void
    {
        $usersCount = $role->users()->count();

        if ($usersCount > 0) {
            throw new InvalidArgumentException(
                "Cannot delete role '{$role->name}' - it has {$usersCount} user(s) assigned."
            );
        }

        $role->delete();
    }

    /**
     * Get all available permissions for the current tenant.
     * Filters based on tenant's allowed_permissions.
     */
    public function getAllPermissions(?Tenant $tenant = null): Collection
    {
        $allPermissions = Permission::orderBy('name')->pluck('name');

        $tenant = $tenant ?? tenant();

        if (!$tenant) {
            return $allPermissions;
        }

        return collect($tenant->filterAllowedPermissions($allPermissions->toArray()));
    }

    /**
     * Get all permissions (unfiltered) - for system admin overview.
     */
    public function getAllPermissionsUnfiltered(): Collection
    {
        return Permission::orderBy('name')->pluck('name');
    }

    /**
     * Get all available role types.
     */
    public function getRoleTypes(): array
    {
        return Role::pluck('type')->unique()->values()->toArray();
    }
}
