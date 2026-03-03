<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Tenant Invitation model for inviting users to this organization.
 *
 * Supports both employee and guest invitations with Schema.org InviteAction format.
 *
 * @see https://schema.org/InviteAction
 */
class Invitation extends Model
{
    use HasFactory;

    // No $connection specified - uses tenant connection via Stancl/tenancy

    protected $fillable = [
        'email',
        'token',
        'role',
        'status',
        'invited_by',
        'accepted_user_id',
        'employment_type',
        'home_organization_id',
        'home_organization_name',
        'named_position',
        'expires_at',
        'accepted_at',
        'declined_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Invitation $invitation) {
            if (empty($invitation->token)) {
                // Token format: tenant_slug:random_string
                $tenantSlug = tenant()?->slug ?? 'unknown';
                $invitation->token = $tenantSlug . ':' . Str::random(32);
            }
            if (empty($invitation->expires_at)) {
                $invitation->expires_at = now()->addDays(7);
            }
            if (empty($invitation->employment_type)) {
                $invitation->employment_type = 'employee';
            }
            if (empty($invitation->role)) {
                $invitation->role = 'user';
            }
        });
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * The user who sent this invitation.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * The user who accepted this invitation.
     */
    public function acceptedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_user_id');
    }

    /**
     * For guests: the organization they come from (cross-database to central).
     */
    public function homeOrganization(): BelongsTo
    {
        return $this->setConnection('central')
            ->belongsTo(Tenant::class, 'home_organization_id');
    }

    // =========================================================================
    // Employment Type Helpers
    // =========================================================================

    public function isEmployeeInvite(): bool
    {
        return $this->employment_type === 'employee';
    }

    public function isGuestInvite(): bool
    {
        return $this->employment_type === 'guest';
    }

    /**
     * Get the home organization display name (from relation or free text).
     */
    public function getHomeOrganizationDisplayName(): ?string
    {
        if ($this->home_organization_id) {
            $tenant = Tenant::on('central')->find($this->home_organization_id);
            if ($tenant) {
                return $tenant->name;
            }
        }

        return $this->home_organization_name;
    }

    // =========================================================================
    // Status Helpers
    // =========================================================================

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && ! $this->isExpired();
    }

    public function markAsAccepted(User $user): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_user_id' => $user->id,
            'accepted_at' => now(),
        ]);
    }

    public function markAsDeclined(): void
    {
        $this->update([
            'status' => 'declined',
            'declined_at' => now(),
        ]);
    }

    // =========================================================================
    // User Creation
    // =========================================================================

    /**
     * Create a User from this invitation.
     * Called when the invitation is accepted.
     */
    public function createUser(string $name, string $password): User
    {
        return User::create([
            'name' => $name,
            'email' => $this->email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'active' => true,
            'role_name' => $this->role,
            'named_position' => $this->named_position,
            'employment_type' => $this->employment_type ?? 'employee',
            'home_organization_id' => $this->home_organization_id,
            'home_organization_name' => $this->home_organization_name,
        ]);
    }

    // =========================================================================
    // Static Finders
    // =========================================================================

    /**
     * Find an invitation by its token.
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('token', $token)->first();
    }

    // =========================================================================
    // Schema.org Output
    // =========================================================================

    /**
     * Convert to Schema.org InviteAction JSON-LD format.
     *
     * @see https://schema.org/InviteAction
     */
    public function toSchemaOrg(): array
    {
        $data = [
            '@type' => 'InviteAction',
            'identifier' => $this->id,
            'actionStatus' => $this->getSchemaOrgStatus(),
            'recipient' => [
                '@type' => 'Person',
                'email' => $this->email,
            ],
            'object' => [
                '@type' => 'EmployeeRole',
                'roleName' => $this->role,
                'employmentType' => $this->employment_type,
            ],
        ];

        if ($this->named_position) {
            $data['object']['namedPosition'] = $this->named_position;
        }

        if ($this->invitedBy) {
            $data['agent'] = $this->invitedBy->toSchemaOrg();
        }

        if ($this->isGuestInvite()) {
            $homeOrgName = $this->getHomeOrganizationDisplayName();
            if ($homeOrgName) {
                $data['object']['description'] = "Guest from: {$homeOrgName}";
            }
        }

        return $data;
    }

    /**
     * Map internal status to Schema.org ActionStatusType.
     */
    private function getSchemaOrgStatus(): string
    {
        return match ($this->status) {
            'pending' => $this->isExpired() ? 'FailedActionStatus' : 'PotentialActionStatus',
            'accepted' => 'CompletedActionStatus',
            'declined' => 'FailedActionStatus',
            'expired' => 'FailedActionStatus',
            default => 'PotentialActionStatus',
        };
    }
}
