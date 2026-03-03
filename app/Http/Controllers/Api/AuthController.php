<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\UserResource;
use App\Models\TenantRegistrationToken;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Lookup organizations for a given email.
     * Returns all organizations where the user has an account.
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $organizations = $this->authService->lookup($request->email);

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'SearchAction',
            'actionStatus' => 'CompletedActionStatus',
            'result' => array_map(fn ($org) => [
                '@type' => 'Organization',
                'identifier' => $org['id'],
                'name' => $org['name'],
                'url' => $org['slug'] ?? null,
            ], $organizations),
        ]);
    }

    /**
     * Login a user within the current tenant context.
     * User object contains role/employment info.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->email,
            $request->password
        );

        return $this->resourceResponse(new AuthResource($result['user'], $result['token']));
    }

    /**
     * Logout current user.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse('Action', 'Successfully logged out');
    }

    /**
     * Get current user info including employment role.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json($user->toSchemaOrg());
    }

    /**
     * Change password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->authService->changePassword($request->user(), $request->password);

        return $this->successResponse('UpdateAction', 'Password changed successfully');
    }

    /**
     * Request password reset email.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $this->authService->sendPasswordReset($request->email);

        return $this->successResponse(
            'Action',
            'If an account exists with this email, you will receive password reset instructions.'
        );
    }

    /**
     * Reset password with token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->authService->resetPassword($request->email, $request->token, $request->password);

        return $this->successResponse('UpdateAction', 'Password has been reset successfully.');
    }

    /**
     * Register admin user from tenant registration token.
     * Creates user in tenant database and adds to central lookup.
     */
    public function registerAdmin(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $registrationToken = TenantRegistrationToken::findByToken($request->token);

        if (! $registrationToken) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired registration token.'],
            ]);
        }

        if ($registrationToken->isExpired()) {
            throw ValidationException::withMessages([
                'token' => ['This registration link has expired.'],
            ]);
        }

        // Tenant context is already initialized by findByToken
        $tenant = tenant();
        if (! $tenant) {
            throw ValidationException::withMessages([
                'token' => ['Organization not found.'],
            ]);
        }

        $result = DB::transaction(function () use ($request, $registrationToken) {
            // Create user in tenant database
            $user = User::create([
                'name' => $request->name,
                'email' => $registrationToken->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
                'active' => true,
                'role_name' => $registrationToken->role,
                'employment_type' => 'employee',
            ]);

            // Assign Spatie role
            $user->assignRole($registrationToken->role);

            // Add to central TenantUser table for lookup
            TenantUser::updateOrCreate(
                ['email' => $user->email, 'tenant_id' => tenant()->id],
                ['user_id' => $user->id]
            );

            // Delete the registration token
            $registrationToken->delete();

            $token = $user->createToken('auth-token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        });

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'RegisterAction',
            'actionStatus' => 'CompletedActionStatus',
            'result' => [
                'user' => $result['user']->toSchemaOrg(),
                'token' => $result['token'],
            ],
        ], 201);
    }
}
