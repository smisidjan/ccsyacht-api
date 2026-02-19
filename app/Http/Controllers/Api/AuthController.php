<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\UserResource;
use App\Models\Invitation;
use App\Models\RegistrationRequest;
use App\Models\TenantUser;
use App\Models\User;
use App\Notifications\PasswordResetNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Look up tenants by email address.
     *
     * Returns a list of tenants where the given email has an account.
     * This endpoint does not require authentication or tenant context.
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $tenantUsers = TenantUser::with('tenant')
            ->where('email', $request->email)
            ->whereHas('tenant', fn($q) => $q->where('active', true))
            ->get();

        $tenants = $tenantUsers->map(fn($tu) => [
            'id' => $tu->tenant->id,
            'name' => $tu->tenant->name,
        ]);

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'SearchAction',
            'actionStatus' => 'CompletedActionStatus',
            'result' => $tenants,
        ]);
    }

    /**
     * Check user status for detailed error messages.
     */
    public function checkUser(LoginRequest $request): JsonResponse
    {
            $pendingRegistration = RegistrationRequest::where('email', $request->email)
                ->where('status', 'pending')
                ->exists();

            if ($pendingRegistration) {
                throw ValidationException::withMessages([
                    'email' => ['Your registration request is pending approval.'],
                ]);
            }

            $rejectedRegistration = RegistrationRequest::where('email', $request->email)
                ->where('status', 'rejected')
                ->exists();

            if ($rejectedRegistration) {
                throw ValidationException::withMessages([
                    'email' => ['Your registration request has been rejected.'],
                ]);
            }

            $pendingInvitation = Invitation::where('email', $request->email)
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
    /*
    *
    */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $this->checkUser($request);
        }

        if (!Hash::check($request->password, $user->password)) {
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

        return response()->json(new AuthResource($user, $token));
    }

     /*
    *
    */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'Action',
            'actionStatus' => 'CompletedActionStatus',
            'description' => 'Successfully logged out',
        ]);
    }

     /*
    *
    */
    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()));
    }

     /*
    *
    */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => $request->password,
        ]);

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'UpdateAction',
            'actionStatus' => 'CompletedActionStatus',
            'description' => 'Password changed successfully',
        ]);
    }

    /**
     * Send password reset link to user's email.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Return success even if user doesn't exist (security)
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'CompletedActionStatus',
                'description' => 'If an account exists with this email, you will receive password reset instructions.',
            ]);
        }

        // Delete any existing tokens for this email
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        // Create new token
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // Send notification
        $user->notify(new PasswordResetNotification($token));

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'Action',
            'actionStatus' => 'CompletedActionStatus',
            'description' => 'If an account exists with this email, you will receive password reset instructions.',
        ]);
    }

    /**
     * Reset password using token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'email' => ['Invalid password reset request.'],
            ]);
        }

        // Check if token is expired (1 hour)
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            throw ValidationException::withMessages([
                'token' => ['This password reset link has expired.'],
            ]);
        }

        // Verify token
        if (!Hash::check($request->token, $record->token)) {
            throw ValidationException::withMessages([
                'token' => ['Invalid password reset token.'],
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }

        // Update password
        $user->update([
            'password' => $request->password,
        ]);

        // Delete the token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'UpdateAction',
            'actionStatus' => 'CompletedActionStatus',
            'description' => 'Password has been reset successfully.',
        ]);
    }
}
