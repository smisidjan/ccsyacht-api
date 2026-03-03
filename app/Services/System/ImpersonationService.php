<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Models\SystemAdmin;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ImpersonationService
{
    /**
     * Start impersonating a tenant user.
     * Returns a temporary token for the impersonated user.
     */
    public function impersonate(SystemAdmin $admin, User $targetUser): array
    {
        // Log the impersonation for audit
        Log::channel('audit')->info('System admin impersonation started', [
            'system_admin_id' => $admin->id,
            'system_admin_email' => $admin->email,
            'target_user_id' => $targetUser->id,
            'target_user_email' => $targetUser->email,
            'tenant_id' => tenant()?->id,
            'timestamp' => now()->toIso8601String(),
        ]);

        // Create a short-lived token for the impersonated user
        $token = $targetUser->createToken(
            'impersonation-' . $admin->id,
            ['*'],
            now()->addHours(1)
        );

        return [
            'token' => $token->plainTextToken,
            'user' => $targetUser,
            'expires_at' => now()->addHours(1)->toIso8601String(),
            'impersonator' => [
                'id' => $admin->id,
                'email' => $admin->email,
            ],
        ];
    }

    /**
     * End impersonation by revoking tokens created for impersonation.
     */
    public function endImpersonation(SystemAdmin $admin, User $user): int
    {
        // Delete impersonation tokens for this user created by this admin
        $deleted = $user->tokens()
            ->where('name', 'like', 'impersonation-' . $admin->id . '%')
            ->delete();

        Log::channel('audit')->info('System admin impersonation ended', [
            'system_admin_id' => $admin->id,
            'target_user_id' => $user->id,
            'tokens_revoked' => $deleted,
            'timestamp' => now()->toIso8601String(),
        ]);

        return $deleted;
    }
}
