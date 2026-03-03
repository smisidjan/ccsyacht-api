<?php

declare(strict_types=1);

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

    /**
     * Lookup organizations where a user has an account.
     * Queries the central TenantUser table.
     *
     * @return array<array{id: string, name: string, slug: string}>
     */
    public function lookup(string $email): array
    {
        $tenantUsers = TenantUser::with('tenant')
            ->where('email', $email)
            ->whereHas('tenant', fn ($q) => $q->where('active', true))
            ->get();

        return $tenantUsers->map(fn (TenantUser $tu) => [
            'id' => $tu->tenant->id,
            'name' => $tu->tenant->name,
            'slug' => $tu->tenant->slug,
        ])->toArray();
    }

    /**
     * Alias for lookup() to maintain backwards compatibility.
     *
     * @deprecated Use lookup() instead
     */
    public function lookupTenants(string $email): array
    {
        return $this->lookup($email);
    }

    /**
     * Login a user within the current tenant context.
     * User object contains all role/employment info.
     *
     * @return array{user: User, token: string}
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->checkUserStatus($email);
        }

        if (! $user->password) {
            throw ValidationException::withMessages([
                'email' => ['No password set. Please use forgot password to set your password.'],
            ]);
        }

        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        return [
            'user' => $user,
            'token' => $user->createToken('auth-token')->plainTextToken,
        ];
    }

    /**
     * Check user status and provide appropriate error messages.
     *
     * @throws ValidationException
     */
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

    /**
     * Logout the current user by deleting their access token.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Change the user's password.
     */
    public function changePassword(User $user, string $newPassword): void
    {
        $this->passwordService->changePassword($user, $newPassword);
    }

    /**
     * Send a password reset email.
     */
    public function sendPasswordReset(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return;
        }

        $this->passwordService->sendResetLink($user);
    }

    /**
     * Reset the user's password using a reset token.
     */
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
