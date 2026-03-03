<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestRolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages which roles are allowed for guest users at an organization.
 */
class GuestRolePermissionController extends Controller
{
    /**
     * Get all allowed guest roles for the current organization.
     */
    public function index(Request $request): JsonResponse
    {
        $organizationId = tenant()?->id;

        if (! $organizationId) {
            return $this->errorResponse('Organization not specified.', 400);
        }

        $roles = GuestRolePermission::getAllowedRolesForOrganization($organizationId);

        return $this->resourceResponse([
            '@type' => 'ItemList',
            'itemListElement' => $roles->map(fn ($role) => [
                '@type' => 'Role',
                'roleName' => $role,
            ])->values()->toArray(),
            'numberOfItems' => $roles->count(),
        ]);
    }

    /**
     * Set all allowed guest roles for the organization (replaces existing).
     */
    public function store(Request $request): JsonResponse
    {
        $organizationId = tenant()?->id;

        if (! $organizationId) {
            return $this->errorResponse('Organization not specified.', 400);
        }

        $validated = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'max:50'],
        ]);

        GuestRolePermission::setAllowedRoles($organizationId, $validated['roles']);

        $roles = GuestRolePermission::getAllowedRolesForOrganization($organizationId);

        return $this->resourceResponse([
            '@type' => 'UpdateAction',
            'actionStatus' => 'CompletedActionStatus',
            'result' => [
                '@type' => 'ItemList',
                'itemListElement' => $roles->map(fn ($role) => [
                    '@type' => 'Role',
                    'roleName' => $role,
                ])->values()->toArray(),
                'numberOfItems' => $roles->count(),
            ],
        ]);
    }

    /**
     * Add a single allowed guest role.
     */
    public function addRole(Request $request): JsonResponse
    {
        $organizationId = tenant()?->id;

        if (! $organizationId) {
            return $this->errorResponse('Organization not specified.', 400);
        }

        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:50'],
        ]);

        GuestRolePermission::addAllowedRole($organizationId, $validated['role_name']);

        return $this->successResponse('AddAction', "Role '{$validated['role_name']}' added to allowed guest roles.", 201);
    }

    /**
     * Remove a single allowed guest role.
     */
    public function removeRole(Request $request, string $roleName): JsonResponse
    {
        $organizationId = tenant()?->id;

        if (! $organizationId) {
            return $this->errorResponse('Organization not specified.', 400);
        }

        $removed = GuestRolePermission::removeAllowedRole($organizationId, $roleName);

        if (! $removed) {
            return $this->errorResponse("Role '{$roleName}' was not in the allowed list.", 404);
        }

        return $this->successResponse('DeleteAction', "Role '{$roleName}' removed from allowed guest roles.");
    }

    /**
     * Reset to default guest roles.
     */
    public function reset(Request $request): JsonResponse
    {
        $organizationId = tenant()?->id;

        if (! $organizationId) {
            return $this->errorResponse('Organization not specified.', 400);
        }

        // Delete all custom roles to fall back to defaults
        GuestRolePermission::where('organization_id', $organizationId)->delete();

        $roles = GuestRolePermission::getAllowedRolesForOrganization($organizationId);

        return $this->resourceResponse([
            '@type' => 'UpdateAction',
            'actionStatus' => 'CompletedActionStatus',
            'description' => 'Guest role permissions reset to defaults.',
            'result' => [
                '@type' => 'ItemList',
                'itemListElement' => $roles->map(fn ($role) => [
                    '@type' => 'Role',
                    'roleName' => $role,
                ])->values()->toArray(),
                'numberOfItems' => $roles->count(),
            ],
        ]);
    }
}
