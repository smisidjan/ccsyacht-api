<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Services\System\TenantRoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

/**
 * Controller for system admins to manage tenant roles.
 * All routes require SystemAdminTenantAccess middleware.
 */
class TenantRoleController extends Controller
{
    public function __construct(
        private TenantRoleService $roleService
    ) {}

    /**
     * List all roles in the current tenant.
     */
    public function index(): JsonResponse
    {
        $roles = $this->roleService->list();

        return $this->resourceResponse([
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $roles->map(fn (Role $role) => [
                '@type' => 'Role',
                'id' => $role->uuid,
                'name' => $role->name,
                'additionalType' => $role->type,
                'permissions' => $role->permissions->pluck('name'),
                'usersCount' => $role->users()->count(),
            ]),
            'numberOfItems' => $roles->count(),
            'context' => [
                'tenant' => tenant()->id,
                'accessType' => 'system_admin',
            ],
        ]);
    }

    /**
     * Create a new role.
     */
    public function store(Request $request): JsonResponse
    {
        $allowedPermissions = $this->roleService->getAllPermissions()->toArray();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
            'type' => ['required', 'string', Rule::in($this->roleService->getRoleTypes())],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'exists:permissions,name', Rule::in($allowedPermissions)],
        ]);

        $role = $this->roleService->create(
            $validated['name'],
            $validated['type'],
            $validated['permissions']
        );

        return $this->successWithResult(
            'CreateAction',
            "Role '{$role->name}' created successfully.",
            [
                '@type' => 'Role',
                'id' => $role->uuid,
                'name' => $role->name,
                'additionalType' => $role->type,
                'permissions' => $role->permissions->pluck('name'),
            ],
            201
        );
    }

    /**
     * Show a single role with its permissions.
     */
    public function show(string $uuid): JsonResponse
    {
        $role = $this->roleService->find($uuid);

        return $this->resourceResponse([
            '@context' => 'https://schema.org',
            '@type' => 'Role',
            'id' => $role->uuid,
            'name' => $role->name,
            'additionalType' => $role->type,
            'permissions' => $role->permissions->pluck('name'),
            'usersCount' => $role->users()->count(),
            'context' => [
                'tenant' => tenant()->id,
                'accessType' => 'system_admin',
            ],
        ]);
    }

    /**
     * Update a role.
     */
    public function update(string $uuid, Request $request): JsonResponse
    {
        $role = $this->roleService->find($uuid);
        $allowedPermissions = $this->roleService->getAllPermissions()->toArray();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:50', Rule::unique('roles')->ignore($role->id)],
            'type' => ['sometimes', 'string', Rule::in($this->roleService->getRoleTypes())],
            'permissions' => ['sometimes', 'array', 'min:1'],
            'permissions.*' => ['string', 'exists:permissions,name', Rule::in($allowedPermissions)],
        ]);

        $role = $this->roleService->update($role, $validated);

        return $this->successWithResult(
            'UpdateAction',
            "Role '{$role->name}' updated successfully.",
            [
                '@type' => 'Role',
                'id' => $role->uuid,
                'name' => $role->name,
                'additionalType' => $role->type,
                'permissions' => $role->permissions->pluck('name'),
            ]
        );
    }

    /**
     * Delete a role.
     */
    public function destroy(string $uuid): JsonResponse
    {
        $role = $this->roleService->find($uuid);

        try {
            $roleName = $role->name;
            $this->roleService->delete($role);

            return $this->successResponse('DeleteAction', "Role '{$roleName}' deleted successfully.");
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * List all available permissions.
     */
    public function permissions(): JsonResponse
    {
        $permissions = $this->roleService->getAllPermissions();

        return $this->resourceResponse([
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $permissions,
            'numberOfItems' => $permissions->count(),
            'context' => [
                'tenant' => tenant()->id,
                'accessType' => 'system_admin',
            ],
        ]);
    }

    /**
     * List all available role types.
     */
    public function types(): JsonResponse
    {
        $types = $this->roleService->getRoleTypes();

        return $this->resourceResponse([
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $types,
            'numberOfItems' => count($types),
            'context' => [
                'tenant' => tenant()->id,
                'accessType' => 'system_admin',
            ],
        ]);
    }
}
