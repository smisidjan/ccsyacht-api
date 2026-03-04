<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Project;

use App\Http\Controllers\Controller;
use App\Http\Resources\Project\StageResource;
use App\Models\Area;
use App\Models\LogbookEntry;
use App\Models\Project;
use App\Models\Stage;
use App\Traits\BroadcastsProjectChanges;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class StageController extends Controller
{
    use BroadcastsProjectChanges;
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

        // Log the action
        LogbookEntry::log(
            $project,
            'stage_created',
            "Created stage '{$stage->name}' in area '{$area->name}'",
            $request->user(),
            ['stage_id' => $stage->id, 'stage_name' => $stage->name, 'area_name' => $area->name]
        );

        // Broadcast the change
        $this->broadcastChange($project, 'stage', 'created', $stage);

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

        // Log the action
        LogbookEntry::log(
            $project,
            'stage_updated',
            "Updated stage '{$stage->name}'",
            $request->user(),
            ['stage_id' => $stage->id, 'stage_name' => $stage->name]
        );

        // Broadcast the change
        $this->broadcastChange($project, 'stage', 'updated', $stage);

        return $this->resourceResponse(new StageResource($stage));
    }

    public function updateStatus(string $projectId, string $stageId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $stage = Stage::with('area')->whereHas('area.deck', fn($q) => $q->where('project_id', $project->id))
            ->findOrFail($stageId);

        $oldStatus = $stage->status;

        $validated = $request->validate([
            'status' => ['required', Rule::in(['not_started', 'in_progress', 'completed'])],
        ]);

        $stage->update($validated);

        // Log the action
        LogbookEntry::log(
            $project,
            'stage_status_changed',
            "Changed stage '{$stage->name}' status from '{$oldStatus}' to '{$validated['status']}'",
            $request->user(),
            ['stage_id' => $stage->id, 'stage_name' => $stage->name, 'old_status' => $oldStatus, 'new_status' => $validated['status']]
        );

        // Broadcast the change
        $this->broadcastChange($project, 'stage', 'updated', $stage);

        return $this->resourceResponse(new StageResource($stage));
    }

    public function destroy(string $projectId, string $stageId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $stage = Stage::with('area')->whereHas('area.deck', fn($q) => $q->where('project_id', $project->id))
            ->findOrFail($stageId);

        $stageName = $stage->name;
        $stageId = $stage->id;
        $areaName = $stage->area->name;
        $stage->delete();

        // Log the action
        LogbookEntry::log(
            $project,
            'stage_deleted',
            "Deleted stage '{$stageName}' from area '{$areaName}'",
            $request->user(),
            ['stage_name' => $stageName, 'area_name' => $areaName]
        );

        // Broadcast the change
        $this->broadcastChange($project, 'stage', 'deleted', null, ['id' => $stageId, 'name' => $stageName]);

        return $this->successResponse('DeleteAction', 'Stage deleted successfully.');
    }
}
