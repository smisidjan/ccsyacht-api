<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
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
     */
    public function update(User $user, Project $project): bool
    {
        if (! $this->view($user, $project)) {
            return false;
        }

        return $user->can('edit_projects');
    }

    /**
     * Determine if the user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        if (! $this->view($user, $project)) {
            return false;
        }

        return $user->can('delete_projects');
    }
}
