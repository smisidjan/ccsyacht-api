<?php

namespace App\Services\System;

use App\Models\SystemAdmin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(string $email, string $password): array
    {
        $admin = SystemAdmin::where('email', $email)->first();

        if (!$admin || !Hash::check($password, $admin->password)) {
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

        return ['admin' => $admin, 'token' => $token];
    }

    public function logout(SystemAdmin $admin): void
    {
        $admin->currentAccessToken()->delete();
    }
}
