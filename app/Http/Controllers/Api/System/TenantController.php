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
        // Check if this will be the main organization (ccs-yacht)
        $slug = $request->input('slug') ?? \Illuminate\Support\Str::slug($request->input('name', ''));
        $isMainOrg = $slug === 'ccs-yacht';

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:landlord.tenants,slug'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_name' => ['nullable', 'string', 'max:255'],
            'restricted_permissions' => ['nullable', 'array'],
            'restricted_permissions.*' => ['string'],
        ];

        // Subscription is required for non-main organizations
        if (! $isMainOrg) {
            $rules['subscription'] = ['required', 'array'];
            $rules['subscription.max_projects'] = ['required', 'integer', 'min:1'];
            $rules['subscription.max_users'] = ['required', 'integer', 'min:1'];
        }

        $validated = $request->validate($rules);

        $createdBy = $request->user('system')?->id;
        $tenant = $this->tenantService->create($validated, $createdBy);

        return $this->successWithResult(
            'CreateAction',
            'Tenant created successfully.',
            new TenantResource($tenant),
            201
        );
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load('subscription');

        return $this->resourceResponse(new TenantResource($tenant));
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:landlord.tenants,slug,' . $tenant->id],
            'active' => ['sometimes', 'boolean'],
            'restricted_permissions' => ['sometimes', 'nullable', 'array'],
            'restricted_permissions.*' => ['string'],
            // Subscription updates
            'subscription' => ['sometimes', 'array'],
            'subscription.max_projects' => ['sometimes', 'integer', 'min:1'],
            'subscription.max_users' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'subscription.status' => ['sometimes', 'string', 'in:active,cancelled'],
        ]);

        // Update tenant basic info
        $tenantData = collect($validated)->except('subscription')->toArray();
        if (! empty($tenantData)) {
            $tenant = $this->tenantService->update($tenant, $tenantData);
        }

        // Update subscription if provided
        if (isset($validated['subscription'])) {
            $this->tenantService->updateSubscription($tenant, $validated['subscription']);
            $tenant->load('subscription');
        }

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
