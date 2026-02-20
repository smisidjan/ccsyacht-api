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
}
