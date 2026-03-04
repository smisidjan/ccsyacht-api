<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Project;

use App\Http\Controllers\Controller;
use App\Http\Resources\Project\StageResource;
use App\Models\Area;
use App\Models\Project;
use App\Models\Stage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class StageController extends Controller
{
    public function index(string $projectId, string $areaId): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        $area = Area::whereHas('deck', fn($q) => $q->where('project_id', $project->id))
            ->findOrFail($areaId);

        $stages = $area->stages()
            ->with('area')
            ->ordered()
            ->get();

        return StageResource::collection($stages);
    }

    public function store(string $projectId, string $areaId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $area = Area::whereHas('deck', fn($q) => $q->where('project_id', $project->id))
            ->findOrFail($areaId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::in(['not_started', 'in_progress', 'completed'])],
            'requires_release_form' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        // Auto-increment sort_order if not provided
        if (!isset($validated['sort_order'])) {
            $validated['sort_order'] = $area->stages()->max('sort_order') + 1;
        }

        // Default status
        if (!isset($validated['status'])) {
            $validated['status'] = 'not_started';
        }

        $stage = $area->stages()->create($validated);
        $stage->load('area');

        return $this->resourceResponse(new StageResource($stage), 201);
    }

    public function show(string $projectId, string $stageId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $stage = Stage::with('area.deck')
            ->whereHas('area.deck', fn($q) => $q->where('project_id', $project->id))
            ->findOrFail($stageId);

        return $this->resourceResponse(new StageResource($stage));
    }

    public function update(string $projectId, string $stageId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $stage = Stage::whereHas('area.deck', fn($q) => $q->where('project_id', $project->id))
            ->findOrFail($stageId);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', Rule::in(['not_started', 'in_progress', 'completed'])],
            'requires_release_form' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $stage->update($validated);
        $stage->load('area');

        return $this->resourceResponse(new StageResource($stage));
    }

    public function updateStatus(string $projectId, string $stageId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $stage = Stage::whereHas('area.deck', fn($q) => $q->where('project_id', $project->id))
            ->findOrFail($stageId);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['not_started', 'in_progress', 'completed'])],
        ]);

        $stage->update($validated);
        $stage->load('area');

        return $this->resourceResponse(new StageResource($stage));
    }

    public function destroy(string $projectId, string $stageId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $stage = Stage::whereHas('area.deck', fn($q) => $q->where('project_id', $project->id))
            ->findOrFail($stageId);

        $stage->delete();

        return $this->successResponse('DeleteAction', 'Stage deleted successfully.');
    }
}
