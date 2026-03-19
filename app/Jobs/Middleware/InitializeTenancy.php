<?php

namespace App\Jobs\Middleware;

use App\Models\Tenant;

class InitializeTenancy
{
    /**
     * Handle the queued job.
     */
    public function handle($job, $next)
    {
        // Check if the job has tenant information
        if (property_exists($job, 'tenantId') && $job->tenantId) {
            $tenant = Tenant::find($job->tenantId);
            if ($tenant) {
                tenancy()->initialize($tenant);
            }
        }

        $response = $next($job);

        // End tenancy after job completes
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        return $response;
    }
}