<?php

namespace App\Services\System;

use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class TenantService
{
    public function list(?bool $active = null, int $perPage = 15): LengthAwarePaginator
    {
        return Tenant::query()
            ->when($active !== null, fn($q) => $q->where('active', $active))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function create(array $data): Tenant
    {
        $slug = $data['slug'] ?? Str::slug($data['name']);

        $originalSlug = $slug;
        $counter = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return Tenant::create([
            'name' => $data['name'],
            'slug' => $slug,
            'active' => true,
            'admin_email' => $data['admin_email'],
            'admin_password' => $data['admin_password'],
            'admin_name' => $data['admin_name'] ?? 'Admin',
        ]);
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);

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
}
