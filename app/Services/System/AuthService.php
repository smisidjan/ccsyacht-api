<?php

namespace App\Services\System;

use App\Models\SystemAdmin;
use App\Services\PasswordService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private PasswordService $passwordService
    ) {}

    public function login(string $email, string $password): array
    {
        $admin = SystemAdmin::where('email', $email)->first();

        if (!$admin) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$admin->password) {
            throw ValidationException::withMessages([
                'email' => ['No password set. Please use forgot password to set your password.'],
            ]);
        }

        if (!Hash::check($password, $admin->password)) {
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

    public function changePassword(SystemAdmin $admin, string $newPassword): void
    {
        $this->passwordService->changePassword($admin, $newPassword);
    }

    public function sendPasswordReset(string $email): void
    {
        $admin = SystemAdmin::where('email', $email)->first();

        if (!$admin) {
            return;
        }

        $this->passwordService->sendResetLink(
            $admin,
            'password_reset_tokens',
            'central',
            '/dashboard/system/reset-password'
        );
    }

    public function resetPassword(string $email, string $token, string $newPassword): void
    {
        $this->passwordService->resetPassword(
            $email,
            $token,
            $newPassword,
            SystemAdmin::class,
            'password_reset_tokens',
            'central'
        );
    }
}
