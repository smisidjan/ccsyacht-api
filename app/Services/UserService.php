<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    public function list(
        ?string $role = null,
        ?bool $active = null,
        ?string $search = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return User::query()
            ->when($role, fn($q, $role) => $q->role($role))
            ->when($active !== null, fn($q) => $q->where('active', $active))
            ->when($search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function update(User $user, array $data): User
    {
        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
            unset($data['role']);
        }

        if (!empty($data)) {
            $user->update($data);
        }

        return $user->fresh();
    }

    public function deactivate(User $user, User $currentUser): void
    {
        if ($user->id === $currentUser->id) {
            throw new \InvalidArgumentException('You cannot deactivate your own account.');
        }

        if ($user->hasRole('admin') && User::role('admin')->where('active', true)->count() === 1) {
            throw new \InvalidArgumentException('Cannot deactivate the last active admin user.');
        }

        $user->update(['active' => false]);
        $user->tokens()->delete();
    }
}
