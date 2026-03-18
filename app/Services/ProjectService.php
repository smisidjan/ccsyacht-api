<?php

namespace App\Services;

use App\Models\LogbookEntry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class ProjectService
{
    private const MASTER_TENANT_SLUG = 'ccs-yacht';

    public function list(
        User $user,
        ?string $status = null,
        ?string $projectType = null,
        ?string $search = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return Project::query()
            ->forUser($user)
            ->with(['shipyard', 'creator'])
            ->when($status, fn($q, $status) => $q->where('status', $status))
            ->when($projectType, fn($q, $type) => $q->where('project_type', $type))
            ->when($search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function create(array $data, User $creator): Project
    {
        $this->checkProjectLimit();

        $data['created_by'] = $creator->id;

        $project = Project::create($data);

        // Add creator as member
        $project->members()->create(['user_id' => $creator->id]);

        // Log project creation
        LogbookEntry::log(
            $project,
            'project_created',
            "Project '{$project->name}' was created",
            $creator
        );

        return $project;
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->fresh(['shipyard', 'creator']);
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }

    // =========================================================================
    // Status Transitions
    // =========================================================================

    /**
     * Activate a project (setup/archived -> active).
     * From setup: requires document types with documents, members, signers.
     * From archived: no requirements (reactivation).
     */
    public function activate(Project $project, ?User $user = null): Project
    {
        if (!in_array($project->status, ['setup', 'archived'])) {
            throw new InvalidArgumentException(
                "Project can only be activated from 'setup' or 'archived' status. Current status: {$project->status}"
            );
        }

        $isReactivation = $project->status === 'archived';

        // Only validate requirements when activating from setup
        if (!$isReactivation) {
            $this->validateActivationRequirements($project);
        }

        $project->update(['status' => 'active']);

        $actionType = $isReactivation ? 'project_reactivated' : 'project_activated';
        $description = $isReactivation ? 'Project reactivated' : 'Project activated';

        LogbookEntry::log(
            $project,
            $actionType,
            $description,
            $user,
            null,
            $user ? null : config('app.system_admin_name')
        );

        return $project->fresh(['shipyard', 'creator']);
    }

    /**
     * Complete a project (active -> completed).
     * Future: requires all required stages to be completed.
     */
    public function complete(Project $project, ?User $user = null): Project
    {
        if ($project->status !== 'active') {
            throw new InvalidArgumentException(
                "Project can only be completed from 'active' status. Current status: {$project->status}"
            );
        }

        // TODO: Check if all required stages are completed
        // For now, always allow completion

        $project->update(['status' => 'completed']);

        LogbookEntry::log(
            $project,
            'project_completed',
            'Project completed',
            $user,
            null,
            $user ? null : config('app.system_admin_name')
        );

        return $project->fresh(['shipyard', 'creator']);
    }

    /**
     * Archive a project (active -> archived).
     */
    public function archive(Project $project, ?User $user = null): Project
    {
        if ($project->status !== 'active') {
            throw new InvalidArgumentException(
                "Project can only be archived from 'active' status. Current status: {$project->status}"
            );
        }

        $project->update(['status' => 'archived']);

        LogbookEntry::log(
            $project,
            'project_archived',
            'Project archived',
            $user,
            null,
            $user ? null : config('app.system_admin_name')
        );

        return $project->fresh(['shipyard', 'creator']);
    }

    /**
     * Validate requirements for activating a project.
     */
    private function validateActivationRequirements(Project $project): void
    {
        $errors = [];

        // Check required document types have at least one document
        $requiredTypes = $project->documentTypes()->where('is_required', true)->get();
        foreach ($requiredTypes as $type) {
            if ($type->documents()->count() === 0) {
                $errors[] = "Required document type '{$type->name}' has no documents";
            }
        }

        // Check project has at least one member
        if ($project->members()->count() === 0) {
            $errors[] = 'Project must have at least one member';
        }

        // Check project has at least one signer
        if ($project->signers()->count() === 0) {
            $errors[] = 'Project must have at least one signer';
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException(
                'Cannot activate project: ' . implode('; ', $errors)
            );
        }
    }

    /**
     * Check if a project is editable (not archived or completed).
     */
    public function isEditable(Project $project): bool
    {
        return !in_array($project->status, ['archived', 'completed']);
    }

    private function checkProjectLimit(): void
    {
        $tenant = tenant();

        if (!$tenant) {
            return;
        }

        if ($tenant->slug === self::MASTER_TENANT_SLUG) {
            return;
        }

        $maxProjects = $tenant->subscription?->max_projects ?? 0;
        $currentCount = Project::count();

        if ($currentCount >= $maxProjects) {
            throw new InvalidArgumentException("Project limit reached ({$maxProjects})");
        }
    }
}
