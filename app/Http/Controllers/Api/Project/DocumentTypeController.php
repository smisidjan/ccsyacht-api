<?php

namespace App\Http\Controllers\Api\Project;

use App\Http\Controllers\Controller;
use App\Http\Resources\Project\DocumentTypeResource;
use App\Models\DocumentType;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentTypeController extends Controller
{
    public function index(string $projectId): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        $documentTypes = $project->documentTypes()
            ->withCount('documents')
            ->ordered()
            ->get();

        return DocumentTypeResource::collection($documentTypes);
    }

    public function store(string $projectId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_required' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        // Set default sort_order if not provided
        if (!isset($validated['sort_order'])) {
            $validated['sort_order'] = $project->documentTypes()->max('sort_order') + 1;
        }

        $documentType = $project->documentTypes()->create($validated);
        $documentType->loadCount('documents');

        return $this->resourceResponse(new DocumentTypeResource($documentType), 201);
    }

    public function show(string $projectId, string $typeId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $documentType = $project->documentTypes()->withCount('documents')->findOrFail($typeId);

        return $this->resourceResponse(new DocumentTypeResource($documentType));
    }

    public function update(string $projectId, string $typeId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $documentType = $project->documentTypes()->findOrFail($typeId);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'is_required' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $documentType->update($validated);
        $documentType->refresh();
        $documentType->loadCount('documents');

        return $this->resourceResponse(new DocumentTypeResource($documentType));
    }

    public function destroy(string $projectId, string $typeId): JsonResponse
    {
        $project = Project::findOrFail($projectId);
        $documentType = $project->documentTypes()->findOrFail($typeId);

        // Check if type has documents
        if ($documentType->documents()->exists()) {
            return $this->errorResponse('Cannot delete document type with existing documents.');
        }

        $documentType->delete();

        return $this->successResponse('DeleteAction', 'Document type deleted successfully.');
    }
}
