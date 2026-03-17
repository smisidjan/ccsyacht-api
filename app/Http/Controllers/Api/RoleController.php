<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Role::query()->select(['id', 'name', 'type']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $roles = $query->orderBy('name')->get();

        return $this->resourceResponse($roles);
    }
}
