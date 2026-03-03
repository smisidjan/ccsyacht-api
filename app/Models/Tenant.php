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
            'created_at',
            'updated_at',
        ];
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'data' => 'array',
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
