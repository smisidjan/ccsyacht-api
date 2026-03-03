<?php

declare(strict_types=1);

use Stancl\Tenancy\Contracts\Tenant;

if (!function_exists('tenant')) {
    /**
     * Get the current tenant or a specific attribute.
     *
     * @param string|null $key
     * @return Tenant|mixed|null
     */
    function tenant(?string $key = null): mixed
    {
        if (!app()->bound('currentTenant')) {
            return null;
        }

        $tenant = app('currentTenant');

        if ($key === null) {
            return $tenant;
        }

        return $tenant?->$key;
    }
}
