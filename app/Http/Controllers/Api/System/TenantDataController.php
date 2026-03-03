<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Models\User;
use App\Services\System\ImpersonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for system admins to access tenant data.
 * All routes require SystemAdminTenantAccess middleware.
 */
class TenantDataController extends Controller
{
    public function __construct(
        private ImpersonationService $impersonationService
    ) {}

    /**
     * List all users in the current tenant context.
     */
    public function listUsers(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 50), 100);

        $query = User::query();

        if ($request->has('employment_type')) {
            $query->where('employment_type', $request->input('employment_type'));
        }

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $users = $query->orderBy('name')->paginate($perPage);

        return $this->resourceResponse([
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => collect($users->items())->map(fn (User $u) => $u->toSchemaOrg()),
            'numberOfItems' => $users->total(),
            'pagination' => [
                'currentPage' => $users->currentPage(),
                'lastPage' => $users->lastPage(),
                'perPage' => $users->perPage(),
            ],
            'context' => [
                'tenant' => tenant()->id,
                'accessType' => 'system_admin',
            ],
        ]);
    }

    /**
     * Show a single user.
     */
    public function showUser(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        return $this->resourceResponse(array_merge(
            ['@context' => 'https://schema.org'],
            $user->toSchemaOrg(),
            [
                'context' => [
                    'tenant' => tenant()->id,
                    'accessType' => 'system_admin',
                ],
            ]
        ));
    }

    /**
     * Impersonate a tenant user.
     */
    public function impersonate(Request $request, string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $admin = $request->user('system');

        if (! $user->active) {
            return $this->errorResponse('Cannot impersonate an inactive user.', 400);
        }

        $result = $this->impersonationService->impersonate($admin, $user);

        return $this->successWithResult(
            'ImpersonateAction',
            "Now impersonating user: {$user->name}",
            [
                'token' => $result['token'],
                'expires' => $result['expires_at'],
                'user' => $user->toSchemaOrg(),
                'tenant' => tenant()->id,
            ],
            201
        );
    }

    /**
     * End impersonation for a specific user.
     */
    public function endImpersonation(Request $request, string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $admin = $request->user('system');

        $tokensRevoked = $this->impersonationService->endImpersonation($admin, $user);

        return $this->successResponse(
            'EndImpersonationAction',
            "Impersonation ended. {$tokensRevoked} token(s) revoked."
        );
    }

    /**
     * List all invitations in the current tenant.
     */
    public function listInvitations(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 50), 100);

        $query = Invitation::with('invitedBy');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $invitations = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->resourceResponse([
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => InvitationResource::collection($invitations)->resolve(),
            'numberOfItems' => $invitations->total(),
            'pagination' => [
                'currentPage' => $invitations->currentPage(),
                'lastPage' => $invitations->lastPage(),
                'perPage' => $invitations->perPage(),
            ],
            'context' => [
                'tenant' => tenant()->id,
                'accessType' => 'system_admin',
            ],
        ]);
    }

    /**
     * Get tenant statistics.
     */
    public function stats(): JsonResponse
    {
        return $this->resourceResponse([
            '@context' => 'https://schema.org',
            '@type' => 'Dataset',
            'name' => 'Tenant Statistics',
            'identifier' => tenant()->id,
            'data' => [
                'totalUsers' => User::count(),
                'activeUsers' => User::where('active', true)->count(),
                'inactiveUsers' => User::where('active', false)->count(),
                'employees' => User::where('employment_type', 'employee')->count(),
                'guests' => User::where('employment_type', 'guest')->count(),
                'pendingInvitations' => Invitation::where('status', 'pending')
                    ->where('expires_at', '>', now())
                    ->count(),
                'acceptedInvitations' => Invitation::where('status', 'accepted')->count(),
            ],
            'context' => [
                'tenant' => tenant()->id,
                'accessType' => 'system_admin',
            ],
        ]);
    }
}
