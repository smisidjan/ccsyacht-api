<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\SystemAdminResource;
use App\Services\System\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authService->login($request->email, $request->password);

        return $this->successWithResult(
            'LoginAction',
            'Successfully logged in.',
            [
                'admin' => new SystemAdminResource($result['admin']),
                'token' => $result['token'],
            ]
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user('system'));

        return $this->successResponse('LogoutAction', 'Successfully logged out.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->resourceResponse(new SystemAdminResource($request->user('system')));
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $this->authService->sendPasswordReset($request->email);

        return $this->successResponse(
            'ForgotPasswordAction',
            'If the email exists, a password reset link has been sent.'
        );
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->authService->resetPassword(
            $request->email,
            $request->token,
            $request->password
        );

        return $this->successResponse(
            'ResetPasswordAction',
            'Password has been reset successfully.'
        );
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string', 'current_password:system'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->authService->changePassword(
            $request->user('system'),
            $request->password
        );

        return $this->successResponse(
            'ChangePasswordAction',
            'Password has been changed successfully.'
        );
    }
}
