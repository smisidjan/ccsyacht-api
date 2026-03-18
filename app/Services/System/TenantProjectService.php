<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\LogbookEntry;
use App\Models\Project;
use App\Models\Shipyard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class TenantProjectService
{
    /**
     * List all projects in the current tenant.
     */
    public function list(
        ?string $status = null,
        ?string $projectType = null,
        ?string $search = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return Project::query()
            ->with(['shipyard', 'creator'])
            ->when($status, fn ($q, $status) => $q->where('status', $status))
            ->when($projectType, fn ($q, $type) => $q->where('project_type', $type))
            ->when($search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find a project by UUID.
     */
    public function find(string $uuid): Project
    {
        return Project::with(['shipyard', 'creator', 'documentTypes' => function ($q) {
            $q->withCount('documents')->ordered();
        }])->findOrFail($uuid);
    }

    /**
     * Create a new project.
     * No subscription limit check for system admin.
     * System admin created projects are attributed to the configured system admin name.
     */
    public function create(array $data): Project
    {
        $data['created_by'] = null;
        $data['created_by_name'] = config('app.system_admin_actor_name');

        $project = Project::create($data);

        return $project->load(['shipyard', 'creator']);
    }

    /**
     * Update a project.
     */
    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->fresh(['shipyard', 'creator']);
    }

    /**
     * Delete a project.
     */
    public function delete(Project $project): void
    {
        // Delete associated files from storage
        Storage::disk('local')->deleteDirectory("projects/{$project->id}");

        $project->delete();
    }

    /**
     * Upload a General Arrangement file.
     */
    public function uploadGeneralArrangement(Project $project, UploadedFile $file): string
    {
        // Delete old file if exists
        if ($project->general_arrangement_path) {
            Storage::disk('local')->delete($project->general_arrangement_path);
        }

        $path = $file->store("projects/{$project->id}/general-arrangement", 'local');
        $project->update(['general_arrangement_path' => $path]);

        return $path;
    }

    /**
     * Delete General Arrangement file.
     *
     * @throws InvalidArgumentException if project has decks or areas
     */
    public function deleteGeneralArrangement(Project $project): void
    {
        if (!$project->general_arrangement_path) {
            return;
        }

        // Check if project has decks (which may contain areas)
        if ($project->decks()->exists()) {
            throw new InvalidArgumentException(
                'Cannot delete General Arrangement - project has decks attached.'
            );
        }

        Storage::disk('local')->delete($project->general_arrangement_path);
        $project->update(['general_arrangement_path' => null]);
    }

    // =========================================================================
    // Document Types
    // =========================================================================

    /**
     * Get all document types for a project.
     */
    public function getDocumentTypes(Project $project): Collection
    {
        return $project->documentTypes()
            ->withCount('documents')
            ->ordered()
            ->get();
    }

    /**
     * Create a document type.
     */
    public function createDocumentType(Project $project, array $data): DocumentType
    {
        // Set default sort_order if not provided
        if (!isset($data['sort_order'])) {
            $data['sort_order'] = $project->documentTypes()->max('sort_order') + 1;
        }

        $documentType = $project->documentTypes()->create($data);
        $documentType->loadCount('documents');

        return $documentType;
    }

    /**
     * Update a document type.
     */
    public function updateDocumentType(DocumentType $documentType, array $data): DocumentType
    {
        $documentType->update($data);
        $documentType->refresh();
        $documentType->loadCount('documents');

        return $documentType;
    }

    /**
     * Delete a document type.
     *
     * @throws InvalidArgumentException if type has documents
     */
    public function deleteDocumentType(DocumentType $documentType): void
    {
        if ($documentType->documents()->exists()) {
            throw new InvalidArgumentException(
                "Cannot delete document type '{$documentType->name}' - it has documents."
            );
        }

        $documentType->delete();
    }

    // =========================================================================
    // Documents
    // =========================================================================

    /**
     * Get all documents for a project.
     */
    public function getDocuments(Project $project, ?string $documentTypeId = null): Collection
    {
        $query = Document::query()
            ->with(['uploader', 'documentType'])
            ->whereHas('documentType', function ($q) use ($project) {
                $q->where('project_id', $project->id);
            });

        if ($documentTypeId) {
            $query->where('document_type_id', $documentTypeId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Find a document.
     */
    public function findDocument(Project $project, string $documentId): Document
    {
        return Document::with(['uploader', 'documentType'])
            ->whereHas('documentType', function ($q) use ($project) {
                $q->where('project_id', $project->id);
            })
            ->findOrFail($documentId);
    }

    /**
     * Upload a document.
     * System admin uploads are attributed to the configured system admin name.
     */
    public function uploadDocument(DocumentType $documentType, UploadedFile $file, array $data): Document
    {
        $project = $documentType->project;
        $path = $file->store("projects/{$project->id}/documents/{$documentType->id}", 'local');

        $document = $documentType->documents()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => null,
            'uploaded_by_name' => config('app.system_admin_actor_name'),
        ]);

        return $document->load(['uploader', 'documentType']);
    }

    /**
     * Delete a document.
     */
    public function deleteDocument(Document $document): void
    {
        Storage::disk('local')->delete($document->file_path);
        $document->delete();
    }

    // =========================================================================
    // Shipyards
    // =========================================================================

    /**
     * Get all shipyards.
     */
    public function getShipyards(): Collection
    {
        return Shipyard::orderBy('name')->get();
    }

    /**
     * Find a shipyard by UUID.
     */
    public function findShipyard(string $uuid): Shipyard
    {
        return Shipyard::findOrFail($uuid);
    }

    /**
     * Create a new shipyard.
     */
    public function createShipyard(array $data): Shipyard
    {
        return Shipyard::create($data);
    }

    /**
     * Update a shipyard.
     */
    public function updateShipyard(Shipyard $shipyard, array $data): Shipyard
    {
        $shipyard->update($data);

        return $shipyard->fresh();
    }

    /**
     * Delete a shipyard.
     *
     * @throws InvalidArgumentException if shipyard has projects
     */
    public function deleteShipyard(Shipyard $shipyard): void
    {
        if ($shipyard->projects()->exists()) {
            throw new InvalidArgumentException(
                "Cannot delete shipyard '{$shipyard->name}' - it has projects."
            );
        }

        $shipyard->delete();
    }

    // =========================================================================
    // Status Transitions
    // =========================================================================

    /**
     * Activate a project (setup/archived -> active).
     * System admin can always activate without validation requirements.
     */
    public function activate(Project $project): Project
    {
        if (!in_array($project->status, ['setup', 'archived'])) {
            throw new InvalidArgumentException(
                "Project can only be activated from 'setup' or 'archived' status. Current status: {$project->status}"
            );
        }

        $isReactivation = $project->status === 'archived';
        $project->update(['status' => 'active']);

        $actionType = $isReactivation ? 'project_reactivated' : 'project_activated';
        $description = $isReactivation ? 'Project reactivated' : 'Project activated';

        LogbookEntry::logSystemAdmin($project, $actionType, $description);

        return $project->fresh(['shipyard', 'creator']);
    }

    /**
     * Complete a project (active -> completed).
     */
    public function complete(Project $project): Project
    {
        if ($project->status !== 'active') {
            throw new InvalidArgumentException(
                "Project can only be completed from 'active' status. Current status: {$project->status}"
            );
        }

        $project->update(['status' => 'completed']);

        LogbookEntry::logSystemAdmin($project, 'project_completed', 'Project completed');

        return $project->fresh(['shipyard', 'creator']);
    }

    /**
     * Archive a project (active -> archived).
     */
    public function archive(Project $project): Project
    {
        if ($project->status !== 'active') {
            throw new InvalidArgumentException(
                "Project can only be archived from 'active' status. Current status: {$project->status}"
            );
        }

        $project->update(['status' => 'archived']);

        LogbookEntry::logSystemAdmin($project, 'project_archived', 'Project archived');

        return $project->fresh(['shipyard', 'creator']);
    }
}
