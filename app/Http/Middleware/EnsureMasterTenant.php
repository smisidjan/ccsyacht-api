<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the current tenant is the master tenant (CCS Yacht).
 * Organization settings are only available for the master tenant.
 */
class EnsureMasterTenant
{
    /**
     * The slug of the master tenant that can manage settings.
     */
    private const MASTER_TENANT_SLUG = 'ccs-yacht';

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if (! $tenant || $tenant->slug !== self::MASTER_TENANT_SLUG) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'Organization settings are not available for this tenant.',
            ], 403);
        }

        return $next($request);
    }
}
