<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\System\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TenantController extends Controller
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $tenants = $this->tenantService->list(
            $request->has('active') ? $request->boolean('active') : null
        );

        return TenantResource::collection($tenants);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:landlord.tenants,slug'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:8'],
            'admin_name' => ['nullable', 'string', 'max:255'],
        ]);

        $tenant = $this->tenantService->create($validated);

        return $this->successWithResult(
            'CreateAction',
            'Tenant created successfully.',
            new TenantResource($tenant),
            201
        );
    }

    public function show(Tenant $tenant): JsonResponse
    {
        return $this->resourceResponse(new TenantResource($tenant));
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:landlord.tenants,slug,' . $tenant->id],
            'active' => ['sometimes', 'boolean'],
        ]);

        $tenant = $this->tenantService->update($tenant, $validated);

        return $this->successWithResult(
            'UpdateAction',
            'Tenant updated successfully.',
            new TenantResource($tenant)
        );
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->tenantService->delete($tenant);

        return $this->successResponse('DeleteAction', 'Tenant deleted successfully.');
    }

    public function registrationInfo(string $slug): JsonResponse
    {
        $tenant = $this->tenantService->getRegistrationInfo($slug);

        if (!$tenant) {
            return $this->errorResponse('Organization not found or inactive.', 404);
        }

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'identifier' => $tenant->slug,
            'name' => $tenant->name,
        ]);
    }
}
