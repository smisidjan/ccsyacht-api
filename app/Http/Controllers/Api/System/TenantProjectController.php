<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\Project\DocumentResource;
use App\Http\Resources\Project\DocumentTypeResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ShipyardResource;
use App\Models\LogbookEntry;
use App\Services\System\TenantProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Controller for system admins to manage tenant projects.
 * All routes require SystemAdminTenantAccess middleware.
 */
class TenantProjectController extends Controller
{
    public function __construct(
        private TenantProjectService $projectService
    ) {}

    // =========================================================================
    // Project CRUD
    // =========================================================================

    /**
     * List all projects in the current tenant.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $projects = $this->projectService->list(
            $request->status,
            $request->project_type,
            $request->search,
            (int) ($request->per_page ?? 15)
        );

        return ProjectResource::collection($projects);
    }

    /**
     * Create a new project.
     */
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

        $project = $this->projectService->create($validated);

        LogbookEntry::logSystemAdmin(
            $project,
            'project_created',
            "Project '{$project->name}' created",
            ['project_type' => $project->project_type]
        );

        return $this->successWithResult(
            'CreateAction',
            "Project '{$project->name}' created successfully.",
            new ProjectResource($project),
            201
        );
    }

    /**
     * Show a single project.
     */
    public function show(string $uuid): JsonResponse
    {
        $project = $this->projectService->find($uuid);

        return $this->resourceResponse(new ProjectResource($project));
    }

    /**
     * Update a project.
     */
    public function update(string $uuid, Request $request): JsonResponse
    {
        $project = $this->projectService->find($uuid);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:projects,name,' . $project->id],
            'description' => ['nullable', 'string', 'max:5000'],
            'project_type' => ['sometimes', 'string', 'in:new_built,refit'],
            'status' => ['sometimes', 'string', 'in:setup,active,locked,completed'],
            'shipyard_id' => ['nullable', 'uuid', 'exists:shipyards,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $project = $this->projectService->update($project, $validated);

        LogbookEntry::logSystemAdmin(
            $project,
            'project_updated',
            "Project settings updated",
            ['fields' => array_keys($validated)]
        );

        return $this->successWithResult(
            'UpdateAction',
            "Project '{$project->name}' updated successfully.",
            new ProjectResource($project)
        );
    }

    /**
     * Delete a project.
     */
    public function destroy(string $uuid): JsonResponse
    {
        $project = $this->projectService->find($uuid);
        $projectName = $project->name;

        $this->projectService->delete($project);

        return $this->successResponse('DeleteAction', "Project '{$projectName}' deleted successfully.");
    }

    // =========================================================================
    // General Arrangement
    // =========================================================================

    /**
     * Upload General Arrangement file.
     */
    public function uploadGeneralArrangement(string $uuid, Request $request): JsonResponse
    {
        $project = $this->projectService->find($uuid);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
        ]);

        $this->projectService->uploadGeneralArrangement($project, $request->file('file'));

        LogbookEntry::logSystemAdmin(
            $project,
            'general_arrangement_uploaded',
            'General Arrangement uploaded',
            ['file_name' => $request->file('file')->getClientOriginalName()]
        );

        return $this->successWithResult(
            'UpdateAction',
            'General Arrangement uploaded successfully.',
            new ProjectResource($project->fresh(['shipyard', 'creator']))
        );
    }

    /**
     * Download General Arrangement file.
     */
    public function downloadGeneralArrangement(string $uuid): mixed
    {
        $project = $this->projectService->find($uuid);

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

    /**
     * Delete General Arrangement file.
     */
    public function deleteGeneralArrangement(string $uuid): JsonResponse
    {
        $project = $this->projectService->find($uuid);

        if (!$project->general_arrangement_path) {
            return $this->errorResponse('No general arrangement file to delete.', 404);
        }

        try {
            $this->projectService->deleteGeneralArrangement($project);

            LogbookEntry::logSystemAdmin(
                $project,
                'general_arrangement_deleted',
                'General Arrangement deleted'
            );

            return $this->successResponse('DeleteAction', 'General Arrangement deleted successfully.');
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    // =========================================================================
    // Document Types
    // =========================================================================

    /**
     * List document types for a project.
     */
    public function documentTypes(string $uuid): AnonymousResourceCollection
    {
        $project = $this->projectService->find($uuid);
        $documentTypes = $this->projectService->getDocumentTypes($project);

        return DocumentTypeResource::collection($documentTypes);
    }

    /**
     * Create a document type.
     */
    public function storeDocumentType(string $uuid, Request $request): JsonResponse
    {
        $project = $this->projectService->find($uuid);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_required' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $documentType = $this->projectService->createDocumentType($project, $validated);

        LogbookEntry::logSystemAdmin(
            $project,
            'document_type_created',
            "Document type '{$documentType->name}' created",
            ['document_type_id' => $documentType->id]
        );

        return $this->successWithResult(
            'CreateAction',
            "Document type '{$documentType->name}' created successfully.",
            new DocumentTypeResource($documentType),
            201
        );
    }

    /**
     * Update a document type.
     */
    public function updateDocumentType(string $uuid, string $typeId, Request $request): JsonResponse
    {
        $project = $this->projectService->find($uuid);
        $documentType = $project->documentTypes()->findOrFail($typeId);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'is_required' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $documentType = $this->projectService->updateDocumentType($documentType, $validated);

        LogbookEntry::logSystemAdmin(
            $project,
            'document_type_updated',
            "Document type '{$documentType->name}' updated",
            ['document_type_id' => $documentType->id, 'fields' => array_keys($validated)]
        );

        return $this->successWithResult(
            'UpdateAction',
            "Document type '{$documentType->name}' updated successfully.",
            new DocumentTypeResource($documentType)
        );
    }

    /**
     * Delete a document type.
     */
    public function destroyDocumentType(string $uuid, string $typeId): JsonResponse
    {
        $project = $this->projectService->find($uuid);
        $documentType = $project->documentTypes()->findOrFail($typeId);

        try {
            $typeName = $documentType->name;
            $this->projectService->deleteDocumentType($documentType);

            LogbookEntry::logSystemAdmin(
                $project,
                'document_type_deleted',
                "Document type '{$typeName}' deleted"
            );

            return $this->successResponse('DeleteAction', "Document type '{$typeName}' deleted successfully.");
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    // =========================================================================
    // Documents
    // =========================================================================

    /**
     * List documents for a project.
     */
    public function documents(string $uuid, Request $request): AnonymousResourceCollection
    {
        $project = $this->projectService->find($uuid);
        $documents = $this->projectService->getDocuments($project, $request->document_type_id);

        return DocumentResource::collection($documents);
    }

    /**
     * Upload a document.
     */
    public function storeDocument(string $uuid, string $typeId, Request $request): JsonResponse
    {
        $project = $this->projectService->find($uuid);
        $documentType = $project->documentTypes()->findOrFail($typeId);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'max:51200'], // 50MB max
        ]);

        $document = $this->projectService->uploadDocument(
            $documentType,
            $request->file('file'),
            $validated
        );

        LogbookEntry::logSystemAdmin(
            $project,
            'document_uploaded',
            "Uploaded document '{$document->title}' to {$documentType->name}",
            ['document_id' => $document->id, 'document_type' => $documentType->name]
        );

        return $this->successWithResult(
            'CreateAction',
            "Document '{$document->title}' uploaded successfully.",
            new DocumentResource($document),
            201
        );
    }

    /**
     * Download a document.
     */
    public function downloadDocument(string $uuid, string $docId): mixed
    {
        $project = $this->projectService->find($uuid);
        $document = $this->projectService->findDocument($project, $docId);

        $path = Storage::disk('local')->path($document->file_path);

        if (!file_exists($path)) {
            return $this->errorResponse('File not found.', 404);
        }

        return response()->download($path, $document->file_name, [
            'Content-Type' => $document->mime_type,
        ]);
    }

    /**
     * Delete a document.
     */
    public function destroyDocument(string $uuid, string $docId): JsonResponse
    {
        $project = $this->projectService->find($uuid);
        $document = $this->projectService->findDocument($project, $docId);

        $documentTitle = $document->title;
        $documentType = $document->documentType->name;
        $this->projectService->deleteDocument($document);

        LogbookEntry::logSystemAdmin(
            $project,
            'document_deleted',
            "Deleted document '{$documentTitle}' from {$documentType}",
            ['document_title' => $documentTitle, 'document_type' => $documentType]
        );

        return $this->successResponse('DeleteAction', "Document '{$documentTitle}' deleted successfully.");
    }

    // =========================================================================
    // Shipyards
    // =========================================================================

    /**
     * List all shipyards.
     */
    public function shipyards(): JsonResponse
    {
        $shipyards = $this->projectService->getShipyards();

        return $this->resourceResponse([
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $shipyards->map(fn ($shipyard) => [
                '@type' => 'Organization',
                'identifier' => $shipyard->id,
                'name' => $shipyard->name,
            ]),
            'numberOfItems' => $shipyards->count(),
        ]);
    }

    /**
     * Show a single shipyard.
     */
    public function showShipyard(string $id): JsonResponse
    {
        $shipyard = $this->projectService->findShipyard($id);

        return $this->resourceResponse(new ShipyardResource($shipyard));
    }

    /**
     * Create a new shipyard.
     */
    public function storeShipyard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $shipyard = $this->projectService->createShipyard($validated);

        return $this->successWithResult(
            'CreateAction',
            "Shipyard '{$shipyard->name}' created successfully.",
            new ShipyardResource($shipyard),
            201
        );
    }

    /**
     * Update a shipyard.
     */
    public function updateShipyard(string $id, Request $request): JsonResponse
    {
        $shipyard = $this->projectService->findShipyard($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $shipyard = $this->projectService->updateShipyard($shipyard, $validated);

        return $this->successWithResult(
            'UpdateAction',
            "Shipyard '{$shipyard->name}' updated successfully.",
            new ShipyardResource($shipyard)
        );
    }

    /**
     * Delete a shipyard.
     */
    public function destroyShipyard(string $id): JsonResponse
    {
        $shipyard = $this->projectService->findShipyard($id);

        try {
            $shipyardName = $shipyard->name;
            $this->projectService->deleteShipyard($shipyard);

            return $this->successResponse('DeleteAction', "Shipyard '{$shipyardName}' deleted successfully.");
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
