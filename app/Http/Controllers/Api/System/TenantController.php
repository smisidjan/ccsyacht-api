<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    /**
     * List all tenants.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenants = Tenant::query()
            ->when($request->has('active'), fn($q) => $q->where('active', $request->boolean('active')))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return TenantResource::collection($tenants);
    }

    /**
     * Create a new tenant.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:landlord.tenants,slug'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:8'],
            'admin_name' => ['nullable', 'string', 'max:255'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        // Ensure slug is unique
        $originalSlug = $slug;
        $counter = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'active' => true,
            'admin_email' => $validated['admin_email'],
            'admin_password' => $validated['admin_password'],
            'admin_name' => $validated['admin_name'] ?? 'Admin',
        ]);

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'CreateAction',
            'actionStatus' => 'CompletedActionStatus',
            'result' => new TenantResource($tenant),
        ], 201);
    }

    /**
     * Show a specific tenant.
     */
    public function show(Tenant $tenant): JsonResponse
    {
        return response()->json(new TenantResource($tenant));
    }

    /**
     * Update a tenant.
     */
    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:landlord.tenants,slug,' . $tenant->id],
            'active' => ['sometimes', 'boolean'],
        ]);

        $tenant->update($validated);

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'UpdateAction',
            'actionStatus' => 'CompletedActionStatus',
            'result' => new TenantResource($tenant->fresh()),
        ]);
    }

    /**
     * Delete a tenant.
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        $tenant->delete();

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'DeleteAction',
            'actionStatus' => 'CompletedActionStatus',
            'description' => 'Tenant deleted successfully.',
        ]);
    }

    /**
     * Get tenant info for registration page.
     *
     * Public endpoint - no authentication required.
     * Returns tenant name and slug for display on registration form.
     */
    public function registrationInfo(string $slug): JsonResponse
    {
        $tenant = Tenant::where('slug', $slug)
            ->where('active', true)
            ->first();

        if (!$tenant) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'Organization not found or inactive.',
            ], 404);
        }

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'identifier' => $tenant->slug,
            'name' => $tenant->name,
        ]);
    }
}
