<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Configures which roles guests are allowed to have in an organization.
 * Default: only 'viewer' role is allowed for guests.
 */
class GuestRolePermission extends Model
{
    use HasUuids;

    protected $connection = 'central';

    protected $fillable = [
        'organization_id',
        'allowed_role',
    ];

    /**
     * Default allowed roles for guests when no permissions are configured.
     */
    public const DEFAULT_ALLOWED_ROLES = ['viewer'];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'organization_id');
    }

    // =========================================================================
    // Static Helpers
    // =========================================================================

    /**
     * Get all allowed guest roles for an organization.
     * Returns defaults if none configured.
     */
    public static function getAllowedRolesForOrganization(string $organizationId): Collection
    {
        $roles = static::where('organization_id', $organizationId)
            ->pluck('allowed_role');

        if ($roles->isEmpty()) {
            return collect(self::DEFAULT_ALLOWED_ROLES);
        }

        return $roles;
    }

    /**
     * Check if a role is allowed for guests in an organization.
     */
    public static function isRoleAllowedForGuest(string $organizationId, string $roleName): bool
    {
        return static::getAllowedRolesForOrganization($organizationId)
            ->contains($roleName);
    }

    /**
     * Set allowed roles for an organization (replaces existing).
     */
    public static function setAllowedRoles(string $organizationId, array $roles): void
    {
        // Remove existing
        static::where('organization_id', $organizationId)->delete();

        // Add new
        foreach ($roles as $role) {
            static::create([
                'organization_id' => $organizationId,
                'allowed_role' => $role,
            ]);
        }
    }

    /**
     * Add a single allowed role for an organization.
     */
    public static function addAllowedRole(string $organizationId, string $roleName): self
    {
        return static::firstOrCreate([
            'organization_id' => $organizationId,
            'allowed_role' => $roleName,
        ]);
    }

    /**
     * Remove a single allowed role for an organization.
     */
    public static function removeAllowedRole(string $organizationId, string $roleName): bool
    {
        return static::where('organization_id', $organizationId)
            ->where('allowed_role', $roleName)
            ->delete() > 0;
    }
}
