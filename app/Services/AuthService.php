<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\RegistrationRequest;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private PasswordService $passwordService
    ) {}

    public function lookupTenants(string $email): array
    {
        $tenantUsers = TenantUser::with('tenant')
            ->where('email', $email)
            ->whereHas('tenant', fn($q) => $q->where('active', true))
            ->get();

        return $tenantUsers->map(fn($tu) => [
            'id' => $tu->tenant->id,
            'name' => $tu->tenant->name,
        ])->toArray();
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->checkUserStatus($email);
        }

        if (!$user->password) {
            throw ValidationException::withMessages([
                'email' => ['No password set. Please use forgot password to set your password.'],
            ]);
        }

        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function checkUserStatus(string $email): void
    {
        $pendingRegistration = RegistrationRequest::where('email', $email)
            ->where('status', 'pending')
            ->exists();

        if ($pendingRegistration) {
            throw ValidationException::withMessages([
                'email' => ['Your registration request is pending approval.'],
            ]);
        }

        $rejectedRegistration = RegistrationRequest::where('email', $email)
            ->where('status', 'rejected')
            ->exists();

        if ($rejectedRegistration) {
            throw ValidationException::withMessages([
                'email' => ['Your registration request has been rejected.'],
            ]);
        }

        $pendingInvitation = Invitation::where('email', $email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->exists();

        if ($pendingInvitation) {
            throw ValidationException::withMessages([
                'email' => ['You have a pending invitation. Please check your email to accept it.'],
            ]);
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $this->passwordService->changePassword($user, $newPassword);
    }

    public function sendPasswordReset(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return;
        }

        $this->passwordService->sendResetLink($user);
    }

    public function resetPassword(string $email, string $token, string $newPassword): void
    {
        $this->passwordService->resetPassword(
            $email,
            $token,
            $newPassword,
            User::class
        );
    }
}
