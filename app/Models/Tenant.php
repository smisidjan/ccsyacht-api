<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $connection = 'central';

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'active',
            'restricted_permissions',
            'created_at',
            'updated_at',
        ];
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'data' => 'array',
            'restricted_permissions' => 'array',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function subscription(): HasOne
    {
        return $this->hasOne(TenantSubscription::class);
    }

    // =========================================================================
    // Main Organization Check
    // =========================================================================

    /**
     * Check if this tenant is the main organization (CCS Yacht).
     * Main organization has no limits.
     */
    public function isMainOrganization(): bool
    {
        return $this->slug === 'ccs-yacht';
    }

    // =========================================================================
    // Permission Management
    // =========================================================================

    /**
     * Get the list of restricted permissions for this tenant.
     * Main organization has no restrictions.
     */
    public function getRestrictedPermissions(): array
    {
        if ($this->isMainOrganization()) {
            return [];
        }

        return $this->restricted_permissions ?? [];
    }

    /**
     * Check if a permission is allowed for this tenant.
     * A permission is allowed if it's NOT in the restricted list.
     */
    public function hasPermissionAllowed(string $permission): bool
    {
        if ($this->isMainOrganization()) {
            return true;
        }

        $restricted = $this->restricted_permissions ?? [];

        return !in_array($permission, $restricted, true);
    }

    /**
     * Filter a list of permissions to only those allowed for this tenant.
     * Removes any permissions that are in the restricted list.
     */
    public function filterAllowedPermissions(array $permissions): array
    {
        if ($this->isMainOrganization()) {
            return $permissions;
        }

        $restricted = $this->restricted_permissions ?? [];

        if (empty($restricted)) {
            return $permissions;
        }

        return array_values(array_diff($permissions, $restricted));
    }

    // =========================================================================
    // Limit Checks (for later enforcement)
    // =========================================================================

    /**
     * Check if this tenant can create more projects.
     */
    public function canCreateProject(): bool
    {
        if ($this->isMainOrganization()) {
            return true;
        }

        $subscription = $this->subscription;

        if (! $subscription || ! $subscription->isActive()) {
            return false;
        }

        if ($subscription->hasUnlimitedProjects()) {
            return true;
        }

        // TODO: When projects are implemented, check count
        // return $this->projects()->count() < $subscription->max_projects;
        return true;
    }

    /**
     * Check if this tenant can create more users.
     */
    public function canCreateUser(): bool
    {
        if ($this->isMainOrganization()) {
            return true;
        }

        $subscription = $this->subscription;

        if (! $subscription || ! $subscription->isActive()) {
            return false;
        }

        if ($subscription->hasUnlimitedUsers()) {
            return true;
        }

        // Note: This needs to be called within tenant context
        // return User::count() < $subscription->max_users;
        return true;
    }

    // =========================================================================
    // Schema.org Output
    // =========================================================================

    /**
     * Convert to Schema.org Organization JSON-LD format.
     *
     * @see https://schema.org/Organization
     */
    public function toSchemaOrg(): array
    {
        $data = [
            '@type' => 'Organization',
            'identifier' => $this->id,
            'name' => $this->name,
            'url' => $this->slug,
        ];

        // Include subscription as Offer (if exists)
        if ($this->relationLoaded('subscription') && $this->subscription) {
            $data['makesOffer'] = $this->subscription->toSchemaOrg();
        }

        return $data;
    }
}
