<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->when($request->role, fn($q, $role) => $q->role($role))
            ->when($request->has('active'), fn($q) => $q->where('active', $request->boolean('active')))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return UserResource::collection($users);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(new UserResource($user));
    }

    public function update(User $user, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['sometimes', 'string', 'in:admin,main user,invitation manager,user,yard,surveyor,painter,owner representative'],
            'active' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
            unset($validated['role']);
        }

        if (!empty($validated)) {
            $user->update($validated);
        }

        return response()->json(new UserResource($user->fresh()));
    }

    public function destroy(User $user, Request $request): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'You cannot deactivate your own account.',
            ], 400);
        }

        if ($user->hasRole('admin') && User::role('admin')->where('active', true)->count() === 1) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'Cannot deactivate the last active admin user.',
            ], 400);
        }

        $user->update(['active' => false]);
        $user->tokens()->delete();

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'DeactivateAction',
            'actionStatus' => 'CompletedActionStatus',
            'description' => 'User deactivated successfully.',
        ]);
    }
}
