<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Project;

use App\Http\Controllers\Controller;
use App\Http\Resources\Project\AreaResource;
use App\Models\Area;
use App\Models\Deck;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AreaController extends Controller
{
    public function index(string $projectId, Request $request): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        $query = Area::query()
            ->with('deck')
            ->withCount('stages')
            ->whereHas('deck', fn($q) => $q->where('project_id', $project->id));

        // Filter by deck if provided
        if ($request->deck_id) {
            $query->where('deck_id', $request->deck_id);
        }

        $areas = $query->ordered()->get();

        return AreaResource::collection($areas);
    }

    public function store(string $projectId, string $deckId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $deck = $project->decks()->findOrFail($deckId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        // Auto-increment sort_order if not provided
        if (!isset($validated['sort_order'])) {
            $validated['sort_order'] = $deck->areas()->max('sort_order') + 1;
        }

        $area = $deck->areas()->create($validated);
        $area->load('deck');
        $area->loadCount('stages');

        return $this->resourceResponse(new AreaResource($area), 201);
    }

    public function show(string $projectId, string $areaId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $area = Area::with(['deck', 'stages' => fn($q) => $q->ordered()])
            ->withCount('stages')
            ->whereHas('deck', fn($q) => $q->where('project_id', $project->id))
            ->findOrFail($areaId);

        return $this->resourceResponse(new AreaResource($area));
    }

    public function update(string $projectId, string $areaId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $area = Area::whereHas('deck', fn($q) => $q->where('project_id', $project->id))
            ->findOrFail($areaId);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $area->update($validated);
        $area->load('deck');
        $area->loadCount('stages');

        return $this->resourceResponse(new AreaResource($area));
    }

    public function destroy(string $projectId, string $areaId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $area = Area::whereHas('deck', fn($q) => $q->where('project_id', $project->id))
            ->findOrFail($areaId);

        $area->delete();

        return $this->successResponse('DeleteAction', 'Area deleted successfully.');
    }
}
