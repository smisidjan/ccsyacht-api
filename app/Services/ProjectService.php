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
        ?string $status = null,
        ?string $projectType = null,
        ?string $search = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return Project::query()
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

    public function updateStatus(Project $project, string $status): Project
    {
        $allowedStatuses = ['setup', 'active', 'locked', 'completed'];

        if (!in_array($status, $allowedStatuses)) {
            throw new InvalidArgumentException("Invalid status: {$status}");
        }

        $project->update(['status' => $status]);

        return $project->fresh();
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
