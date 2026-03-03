<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GuestRolePermission;
use App\Models\Invitation;
use App\Models\TenantUser;
use App\Models\User;
use App\Notifications\InvitationSentNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

class InvitationService
{
    /**
     * List invitations for the current tenant.
     */
    public function list(?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return Invitation::with('invitedBy')
            ->when($status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create an invitation for an employee or guest.
     *
     * @throws InvalidArgumentException If validation fails
     */
    public function create(
        string $email,
        string $role,
        User $invitedBy,
        string $employmentType = 'employee',
        ?string $homeOrganizationId = null,
        ?string $homeOrganizationName = null,
        ?string $namedPosition = null,
    ): Invitation {
        // Validate employment type
        if (! in_array($employmentType, ['employee', 'guest'])) {
            throw new InvalidArgumentException('Employment type must be "employee" or "guest".');
        }

        // Validate guest has home organization info
        if ($employmentType === 'guest' && empty($homeOrganizationId) && empty($homeOrganizationName)) {
            throw new InvalidArgumentException('Guest invitations must include a home organization (ID or name).');
        }

        // Validate guest role is allowed
        if ($employmentType === 'guest') {
            $tenantId = tenant()?->id;
            if ($tenantId && ! GuestRolePermission::isRoleAllowedForGuest($tenantId, $role)) {
                $allowedRoles = GuestRolePermission::getAllowedRolesForOrganization($tenantId)->implode(', ');
                throw new InvalidArgumentException(
                    "Role '{$role}' is not allowed for guests. Allowed roles: {$allowedRoles}"
                );
            }
        }

        // Check if user already exists in this tenant
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            throw new InvalidArgumentException('This user already exists in this organization.');
        }

        // Check for pending invitation
        $pendingInvitation = Invitation::where('email', $email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->exists();

        if ($pendingInvitation) {
            throw new InvalidArgumentException('A pending invitation already exists for this email.');
        }

        $invitation = Invitation::create([
            'email' => $email,
            'role' => $role,
            'invited_by' => $invitedBy->id,
            'employment_type' => $employmentType,
            'home_organization_id' => $homeOrganizationId,
            'home_organization_name' => $homeOrganizationName,
            'named_position' => $namedPosition,
        ]);

        $invitation->load('invitedBy');

        Notification::route('mail', $email)
            ->notify(new InvitationSentNotification($invitation));

        return $invitation;
    }

    /**
     * Find an invitation by its token.
     * Token format: tenant_slug:random_string
     */
    public function findByToken(string $token): ?Invitation
    {
        if (! str_contains($token, ':')) {
            return null;
        }

        [$tenantSlug, $actualToken] = explode(':', $token, 2);

        // Initialize tenant if not already
        $tenant = \App\Models\Tenant::where('slug', $tenantSlug)
            ->where('active', true)
            ->first();

        if (! $tenant) {
            return null;
        }

        tenancy()->initialize($tenant);

        return Invitation::with('invitedBy')
            ->where('token', $token)
            ->first();
    }

    /**
     * Accept an invitation and create the user.
     *
     * @return array{user: User, token: string}
     */
    public function accept(Invitation $invitation, string $name, string $password): array
    {
        if (! $invitation->isPending()) {
            throw new InvalidArgumentException('This invitation is no longer valid.');
        }

        return DB::transaction(function () use ($invitation, $name, $password) {
            // Create the user in this tenant's database
            $user = $invitation->createUser($name, $password);

            // Ensure the role exists in Spatie (for dynamic guest roles)
            Role::firstOrCreate(
                ['name' => $invitation->role, 'guard_name' => 'web']
            );

            // Assign Spatie role
            $user->assignRole($invitation->role);

            // Add to central TenantUser table for lookup
            TenantUser::updateOrCreate(
                ['email' => $user->email, 'tenant_id' => tenant()->id],
                ['user_id' => $user->id]
            );

            // Mark invitation as accepted
            $invitation->markAsAccepted($user);

            // Create auth token
            $token = $user->createToken('auth-token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        });
    }

    /**
     * Decline an invitation.
     */
    public function decline(Invitation $invitation): void
    {
        if ($invitation->status !== 'pending') {
            throw new InvalidArgumentException('This invitation cannot be declined.');
        }

        $invitation->markAsDeclined();
    }

    /**
     * Resend an invitation email and extend expiration.
     */
    public function resend(Invitation $invitation): void
    {
        if ($invitation->status !== 'pending') {
            throw new InvalidArgumentException('Only pending invitations can be resent.');
        }

        $invitation->update([
            'expires_at' => now()->addDays(7),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new InvitationSentNotification($invitation));
    }

    /**
     * Cancel/expire an invitation.
     */
    public function cancel(Invitation $invitation): void
    {
        if ($invitation->status !== 'pending') {
            throw new InvalidArgumentException('Only pending invitations can be cancelled.');
        }

        $invitation->update(['status' => 'expired']);
    }
}
