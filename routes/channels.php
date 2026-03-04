<?php

use App\Models\Project;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Project channel - users can listen if they are a member of the project
 */
Broadcast::channel('project.{projectId}', function ($user, string $projectId) {
    $project = Project::find($projectId);

    if (!$project) {
        return false;
    }

    // Check if user is a member of this project
    return $project->members()->where('user_id', $user->id)->exists();
});
