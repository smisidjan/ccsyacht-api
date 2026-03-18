<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantService
{
    public function list(?bool $active = null, int $perPage = 15): LengthAwarePaginator
    {
        return Tenant::query()
            ->with('subscription')
            ->when($active !== null, fn ($q) => $q->where('active', $active))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function create(array $data, ?string $createdBy = null): Tenant
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $slug = $data['slug'] ?? Str::slug($data['name']);

            $originalSlug = $slug;
            $counter = 1;
            while (Tenant::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            // Merge always_restricted permissions with any provided restricted_permissions
            $restrictedPermissions = $this->mergeRestrictedPermissions(
                $data['restricted_permissions'] ?? [],
                $slug
            );

            $tenant = Tenant::create([
                'name' => $data['name'],
                'slug' => $slug,
                'active' => true,
                'admin_email' => $data['admin_email'],
                'admin_name' => $data['admin_name'] ?? 'Admin',
                'restricted_permissions' => $restrictedPermissions,
            ]);

            // Create subscription if provided (not for main organization)
            if (isset($data['subscription']) && $slug !== 'ccs-yacht') {
                $this->createSubscription($tenant, $data['subscription'], $createdBy);
            }

            return $tenant->load('subscription');
        });
    }

    /**
     * Create a subscription for a tenant.
     */
    public function createSubscription(Tenant $tenant, array $subscriptionData, ?string $createdBy = null): TenantSubscription
    {
        return TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'max_projects' => $subscriptionData['max_projects'] ?? 1,
            'max_users' => $subscriptionData['max_users'] ?? null,
            'status' => 'active',
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Update a tenant's subscription.
     */
    public function updateSubscription(Tenant $tenant, array $subscriptionData): TenantSubscription
    {
        $subscription = $tenant->subscription;

        if ($subscription) {
            $subscription->update($subscriptionData);

            return $subscription->fresh();
        }

        return $this->createSubscription($tenant, $subscriptionData);
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);

        return $tenant->fresh();
    }

    /**
     * Update restricted permissions for a tenant.
     */
    public function updateRestrictedPermissions(Tenant $tenant, array $permissions): Tenant
    {
        $tenant->update(['restricted_permissions' => $permissions]);

        return $tenant->fresh();
    }

    public function delete(Tenant $tenant): void
    {
        $tenant->delete();
    }

    public function getRegistrationInfo(string $slug): ?Tenant
    {
        return Tenant::where('slug', $slug)
            ->where('active', true)
            ->first();
    }

    /**
     * Get permissions that can be selected as restricted (excludes always_restricted).
     * These are the permissions shown in the "Restricted Permissions" UI.
     */
    public function getSelectablePermissions(): array
    {
        $alwaysRestricted = config('permissions.always_restricted', []);
        $allPermissions = config('permissions.all', []);

        $selectable = array_values(array_diff($allPermissions, $alwaysRestricted));
        sort($selectable);

        return $selectable;
    }

    /**
     * Get permissions that are always restricted for non-master tenants.
     */
    public function getAlwaysRestrictedPermissions(): array
    {
        return config('permissions.always_restricted', []);
    }

    /**
     * Merge user-selected restricted permissions with always_restricted permissions.
     * For master tenant (ccs-yacht), no permissions are restricted.
     */
    private function mergeRestrictedPermissions(array $selectedRestrictions, string $slug): ?array
    {
        // Master tenant has no restrictions
        if ($slug === 'ccs-yacht') {
            return null;
        }

        $alwaysRestricted = config('permissions.always_restricted', []);

        // Merge and deduplicate
        return array_values(array_unique(array_merge($alwaysRestricted, $selectedRestrictions)));
    }
}
