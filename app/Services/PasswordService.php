<?php

namespace App\Services;

use App\Notifications\PasswordResetNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordService
{
    public function sendResetLink(
        Authenticatable $user,
        string $table = 'password_reset_tokens',
        ?string $connection = null,
        string $resetPath = '/reset-password'
    ): void {
        $query = $connection
            ? DB::connection($connection)->table($table)
            : DB::table($table);

        $query->where('email', $user->email)->delete();

        $token = Str::random(64);

        $insertQuery = $connection
            ? DB::connection($connection)->table($table)
            : DB::table($table);

        $insertQuery->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $user->notify(new PasswordResetNotification($token, $resetPath));
    }

    public function resetPassword(
        string $email,
        string $token,
        string $newPassword,
        string $modelClass,
        string $table = 'password_reset_tokens',
        ?string $connection = null
    ): void {
        $query = $connection
            ? DB::connection($connection)->table($table)
            : DB::table($table);

        $record = $query->where('email', $email)->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'email' => ['Invalid password reset request.'],
            ]);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            $deleteQuery = $connection
                ? DB::connection($connection)->table($table)
                : DB::table($table);
            $deleteQuery->where('email', $email)->delete();

            throw ValidationException::withMessages([
                'token' => ['This password reset link has expired.'],
            ]);
        }

        if (!Hash::check($token, $record->token)) {
            throw ValidationException::withMessages([
                'token' => ['Invalid password reset token.'],
            ]);
        }

        $user = $modelClass::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }

        $user->update([
            'password' => $newPassword,
        ]);

        $deleteQuery = $connection
            ? DB::connection($connection)->table($table)
            : DB::table($table);
        $deleteQuery->where('email', $email)->delete();
    }

    public function changePassword(Authenticatable $user, string $newPassword): void
    {
        $user->update([
            'password' => $newPassword,
        ]);
    }
}
