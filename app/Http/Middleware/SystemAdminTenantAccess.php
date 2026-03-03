<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows authenticated SystemAdmins to access tenant routes.
 *
 * The SystemAdmin can:
 * - Access any tenant by providing X-Tenant-ID header
 * - View tenant data without being a visible user
 * - Impersonate tenant users for debugging
 */
class SystemAdminTenantAccess
{
    public function __construct(
        protected Tenancy $tenancy
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $systemAdmin = $request->user('system');

        if (! $systemAdmin) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'System admin authentication required.',
            ], 401);
        }

        $tenantId = $request->header('X-Tenant-ID');

        if (! $tenantId) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'X-Tenant-ID header required.',
            ], 400);
        }

        $tenant = Tenant::where('id', $tenantId)
            ->orWhere('slug', $tenantId)
            ->first();

        if (! $tenant) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'Tenant not found.',
            ], 404);
        }

        if (! $tenant->active) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'Tenant is not active.',
            ], 403);
        }

        // Initialize tenant context (without requiring tenant user auth)
        $this->tenancy->initialize($tenant);

        // Mark request as system admin access (for audit logging)
        $request->attributes->set('system_admin_access', true);
        $request->attributes->set('system_admin_id', $systemAdmin->id);

        return $next($request);
    }
}
