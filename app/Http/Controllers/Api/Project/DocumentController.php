<?php

namespace App\Http\Controllers\Api\Project;

use App\Http\Controllers\Controller;
use App\Http\Resources\Project\DocumentResource;
use App\Models\Document;
use App\Models\LogbookEntry;
use App\Models\Project;
use App\Traits\BroadcastsProjectChanges;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    use BroadcastsProjectChanges;
    public function index(string $projectId, Request $request): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('view', $project);

        $query = Document::query()
            ->with(['uploader', 'documentType'])
            ->whereHas('documentType', function ($q) use ($project) {
                $q->where('project_id', $project->id);
            });

        // Filter by document type if provided
        if ($request->document_type_id) {
            $query->where('document_type_id', $request->document_type_id);
        }

        $documents = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return DocumentResource::collection($documents);
    }

    public function indexByType(string $projectId, string $typeId): AnonymousResourceCollection
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('view', $project);

        $documentType = $project->documentTypes()->findOrFail($typeId);

        $documents = $documentType->documents()
            ->with(['uploader', 'documentType'])
            ->orderBy('created_at', 'desc')
            ->get();

        return DocumentResource::collection($documents);
    }

    public function store(string $projectId, string $typeId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('view', $project);

        $documentType = $project->documentTypes()->findOrFail($typeId);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'max:51200'], // 50MB max
        ]);

        $file = $request->file('file');
        $path = $file->store("projects/{$project->id}/documents/{$documentType->id}", 'local');

        $document = $documentType->documents()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => $request->user()->id,
        ]);

        $document->load(['uploader', 'documentType']);

        // Log the action
        LogbookEntry::log(
            $project,
            'document_uploaded',
            "Uploaded document '{$document->title}' to {$documentType->name}",
            $request->user(),
            ['document_id' => $document->id, 'document_type' => $documentType->name]
        );

        // Broadcast the change
        $this->broadcastChange($project, 'document', 'created', $document);

        return $this->resourceResponse(new DocumentResource($document), 201);
    }

    public function show(string $projectId, string $docId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('view', $project);

        $document = Document::with(['uploader', 'documentType'])
            ->whereHas('documentType', function ($q) use ($project) {
                $q->where('project_id', $project->id);
            })
            ->findOrFail($docId);

        return $this->resourceResponse(new DocumentResource($document));
    }

    public function destroy(string $projectId, string $docId, Request $request): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('view', $project);

        $document = Document::with('documentType')->whereHas('documentType', function ($q) use ($project) {
            $q->where('project_id', $project->id);
        })->findOrFail($docId);

        $documentTitle = $document->title;
        $documentId = $document->id;
        $documentType = $document->documentType->name;

        // Delete the file
        Storage::disk('local')->delete($document->file_path);

        $document->delete();

        // Log the action
        LogbookEntry::log(
            $project,
            'document_deleted',
            "Deleted document '{$documentTitle}' from {$documentType}",
            $request->user(),
            ['document_title' => $documentTitle, 'document_type' => $documentType]
        );

        // Broadcast the change
        $this->broadcastChange($project, 'document', 'deleted', null, ['id' => $documentId, 'title' => $documentTitle]);

        return $this->successResponse('DeleteAction', 'Document deleted successfully.');
    }

    public function download(string $projectId, string $docId): mixed
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('view', $project);

        $document = Document::whereHas('documentType', function ($q) use ($project) {
            $q->where('project_id', $project->id);
        })->findOrFail($docId);

        $path = Storage::disk('local')->path($document->file_path);

        if (!file_exists($path)) {
            return $this->errorResponse('File not found.', 404);
        }

        return response()->download($path, $document->file_name, [
            'Content-Type' => $document->mime_type,
        ]);
    }
}
