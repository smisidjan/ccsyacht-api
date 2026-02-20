<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
