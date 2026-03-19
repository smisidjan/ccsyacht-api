<?php

namespace App\Jobs;

use App\Models\Tenant;

trait TenantAware
{
    public ?string $tenantId = null;

    /**
     * Set the tenant for this job.
     */
    public function setTenant(?string $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Initialize tenant context when the job is processing.
     */
    public function initializeTenancy(): void
    {
        if ($this->tenantId) {
            $tenant = Tenant::find($this->tenantId);
            if ($tenant) {
                tenancy()->initialize($tenant);
            }
        }
    }
}