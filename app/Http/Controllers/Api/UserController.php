<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $users = $this->userService->list(
            $request->role,
            $request->has('active') ? $request->boolean('active') : null,
            $request->search
        );

        return UserResource::collection($users);
    }

    public function show(User $user): JsonResponse
    {
        return $this->resourceResponse(new UserResource($user));
    }

    public function update(User $user, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['sometimes', 'string', 'in:admin,main user,invitation manager,user,yard,surveyor,painter,owner representative'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $user = $this->userService->update($user, $validated);

        return $this->resourceResponse(new UserResource($user));
    }

    public function destroy(User $user, Request $request): JsonResponse
    {
        try {
            $this->userService->deactivate($user, $request->user());
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->successResponse('DeactivateAction', 'User deactivated successfully.');
    }
}
