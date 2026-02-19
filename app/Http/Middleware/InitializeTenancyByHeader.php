<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyByHeader
{
    public function __construct(
        protected Tenancy $tenancy
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header(config('tenancy.identification.header', 'X-Tenant-ID'));

        if (!$tenantId) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'Missing X-Tenant-ID header.',
            ], 400);
        }

        $tenant = Tenant::where('id', $tenantId)
            ->orWhere('slug', $tenantId)
            ->first();

        if (!$tenant) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'Tenant not found.',
            ], 404);
        }

        if (!$tenant->active) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'Tenant is not active.',
            ], 403);
        }

        $this->tenancy->initialize($tenant);

        return $next($request);
    }
}
