<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Statuses that make a project read-only for tenant users.
     */
    private const READ_ONLY_STATUSES = ['archived', 'completed'];

    /**
     * Determine if the user can view the project.
     * Employees can view all projects, guests only their own.
     */
    public function view(User $user, Project $project): bool
    {
        if ($user->employment_type !== 'guest') {
            return true;
        }

        return $project->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine if the user can update the project.
     * Denied if project is archived or completed.
     */
    public function update(User $user, Project $project): bool
    {
        if (!$this->view($user, $project)) {
            return false;
        }

        // Archived and completed projects are read-only
        if (in_array($project->status, self::READ_ONLY_STATUSES)) {
            return false;
        }

        return $user->can('edit_projects');
    }

    /**
     * Determine if the user can delete the project.
     * Denied if project is archived or completed.
     */
    public function delete(User $user, Project $project): bool
    {
        if (!$this->view($user, $project)) {
            return false;
        }

        // Archived and completed projects cannot be deleted
        if (in_array($project->status, self::READ_ONLY_STATUSES)) {
            return false;
        }

        return $user->can('delete_projects');
    }

    /**
     * Determine if the user can change the project status.
     * This is separate from update to allow status changes on read-only projects.
     */
    public function changeStatus(User $user, Project $project): bool
    {
        if (!$this->view($user, $project)) {
            return false;
        }

        return $user->can('edit_projects');
    }
}
