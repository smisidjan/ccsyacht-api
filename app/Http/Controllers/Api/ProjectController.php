<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projectService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $projects = $this->projectService->list(
            $request->user(),
            $request->status,
            $request->project_type,
            $request->search,
            $request->per_page ?? 15
        );

        return ProjectResource::collection($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:projects,name'],
            'description' => ['nullable', 'string', 'max:5000'],
            'project_type' => ['required', 'string', 'in:new_built,refit'],
            'shipyard_id' => ['nullable', 'uuid', 'exists:shipyards,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        try {
            $project = $this->projectService->create($validated, $request->user());
            $project->load(['shipyard', 'creator']);

            return $this->resourceResponse(new ProjectResource($project), 201);
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show(string $id): JsonResponse
    {
        $project = Project::with(['shipyard', 'creator'])->findOrFail($id);

        $this->authorize('view', $project);

        return $this->resourceResponse(new ProjectResource($project));
    }

    public function update(string $id, Request $request): JsonResponse
    {
        $project = Project::findOrFail($id);

        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'project_type' => ['sometimes', 'string', 'in:new_built,refit'],
            'status' => ['sometimes', 'string', 'in:setup,active,locked,completed'],
            'shipyard_id' => ['nullable', 'uuid', 'exists:shipyards,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $project = $this->projectService->update($project, $validated);

        return $this->resourceResponse(new ProjectResource($project));
    }

    public function destroy(string $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $this->authorize('delete', $project);

        $this->projectService->delete($project);

        return $this->successResponse('DeleteAction', 'Project deleted successfully.');
    }

    public function uploadGeneralArrangement(string $id, Request $request): JsonResponse
    {
        $project = Project::findOrFail($id);

        $this->authorize('update', $project);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
        ]);

        $file = $request->file('file');
        $path = $file->store("projects/{$project->id}/general-arrangement", 'local');

        // Delete old file if exists
        if ($project->general_arrangement_path) {
            Storage::disk('local')->delete($project->general_arrangement_path);
        }

        $project->update(['general_arrangement_path' => $path]);

        return $this->resourceResponse(new ProjectResource($project->fresh(['shipyard', 'creator'])));
    }

    public function downloadGeneralArrangement(string $id): mixed
    {
        $project = Project::findOrFail($id);

        $this->authorize('view', $project);

        if (!$project->general_arrangement_path) {
            return $this->errorResponse('No general arrangement file uploaded.', 404);
        }

        $path = Storage::disk('local')->path($project->general_arrangement_path);

        if (!file_exists($path)) {
            return $this->errorResponse('File not found.', 404);
        }

        $mimeType = Storage::disk('local')->mimeType($project->general_arrangement_path);
        $fileName = basename($project->general_arrangement_path);

        return response()->download($path, $fileName, [
            'Content-Type' => $mimeType,
        ]);
    }
}
