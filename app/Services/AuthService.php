<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\RegistrationRequest;
use App\Models\TenantUser;
use App\Models\User;
use App\Notifications\PasswordResetNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
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
        $user->update([
            'password' => $newPassword,
        ]);
    }

    public function sendPasswordReset(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return;
        }

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $user->notify(new PasswordResetNotification($token));
    }

    public function resetPassword(string $email, string $token, string $newPassword): void
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'email' => ['Invalid password reset request.'],
            ]);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw ValidationException::withMessages([
                'token' => ['This password reset link has expired.'],
            ]);
        }

        if (!Hash::check($token, $record->token)) {
            throw ValidationException::withMessages([
                'token' => ['Invalid password reset token.'],
            ]);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }

        $user->update([
            'password' => $newPassword,
        ]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }
}
