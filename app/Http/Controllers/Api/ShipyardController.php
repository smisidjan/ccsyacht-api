<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShipyardResource;
use App\Models\Shipyard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShipyardController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Shipyard::query();

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $shipyards = $query->orderBy('name')->paginate($request->per_page ?? 15);

        return ShipyardResource::collection($shipyards);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $shipyard = Shipyard::create($validated);

        return $this->resourceResponse(new ShipyardResource($shipyard), 201);
    }

    public function show(string $id): JsonResponse
    {
        $shipyard = Shipyard::findOrFail($id);

        return $this->resourceResponse(new ShipyardResource($shipyard));
    }

    public function update(string $id, Request $request): JsonResponse
    {
        $shipyard = Shipyard::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $shipyard->update($validated);

        return $this->resourceResponse(new ShipyardResource($shipyard));
    }

    public function destroy(string $id): JsonResponse
    {
        $shipyard = Shipyard::findOrFail($id);

        // Check if shipyard has projects
        if ($shipyard->projects()->exists()) {
            return $this->errorResponse('Cannot delete shipyard with existing projects.');
        }

        $shipyard->delete();

        return $this->successResponse('DeleteAction', 'Shipyard deleted successfully.');
    }
}
