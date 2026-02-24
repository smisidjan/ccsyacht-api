<?php

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
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $tenants = $this->authService->lookupTenants($request->email);

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'SearchAction',
            'actionStatus' => 'CompletedActionStatus',
            'result' => $tenants,
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->email, $request->password);

        return $this->resourceResponse(new AuthResource($result['user'], $result['token']));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse('Action', 'Successfully logged out');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->resourceResponse(new UserResource($request->user()));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->authService->changePassword($request->user(), $request->password);

        return $this->successResponse('UpdateAction', 'Password changed successfully');
    }

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

    public function registerAdmin(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $registrationToken = TenantRegistrationToken::findByToken($request->token);

        if (!$registrationToken) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired registration token.'],
            ]);
        }

        if ($registrationToken->isExpired()) {
            throw ValidationException::withMessages([
                'token' => ['This registration link has expired.'],
            ]);
        }

        $result = DB::transaction(function () use ($request, $registrationToken) {
            $user = User::create([
                'name' => $request->name,
                'email' => $registrationToken->email,
                'password' => $request->password,
                'email_verified_at' => now(),
                'active' => true,
            ]);

            $user->assignRole($registrationToken->role);

            // Sync to central tenant_users table
            TenantUser::updateOrCreate(
                ['email' => $user->email, 'tenant_id' => tenant()->id],
                ['user_id' => $user->id]
            );

            // Delete the registration token
            $registrationToken->delete();

            $token = $user->createToken('auth-token')->plainTextToken;

            return ['user' => $user, 'token' => $token];
        });

        return $this->resourceResponse(new AuthResource($result['user'], $result['token']));
    }
}
