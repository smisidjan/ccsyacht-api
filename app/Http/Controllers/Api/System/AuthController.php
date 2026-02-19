<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\SystemAdminResource;
use App\Models\SystemAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate a system admin and return an access token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = SystemAdmin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$admin->active) {
            throw ValidationException::withMessages([
                'email' => ['This account has been deactivated.'],
            ]);
        }

        $token = $admin->createToken('system-admin-token')->plainTextToken;

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'LoginAction',
            'actionStatus' => 'CompletedActionStatus',
            'result' => [
                'admin' => new SystemAdminResource($admin),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user('system')->currentAccessToken()->delete();

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'LogoutAction',
            'actionStatus' => 'CompletedActionStatus',
            'description' => 'Successfully logged out.',
        ]);
    }

    /**
     * Get the authenticated system admin's information.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(new SystemAdminResource($request->user('system')));
    }
}
