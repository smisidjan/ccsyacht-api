<?php

declare(strict_types=1);

namespace App\Traits;

use App\Events\Project\ProjectDataChanged;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

trait BroadcastsProjectChanges
{
    /**
     * Broadcast a project data change event.
     */
    protected function broadcastChange(
        Project $project,
        string $entityType,
        string $action,
        ?Model $entity = null,
        array $additionalData = []
    ): void {
        $data = $additionalData;

        if ($entity) {
            // If the entity has a toSchemaOrg method, use it
            if (method_exists($entity, 'toSchemaOrg')) {
                $data = array_merge($entity->toSchemaOrg(), $additionalData);
            } else {
                $data = array_merge($entity->toArray(), $additionalData);
            }
        }

        // Capture socket ID for toOthers() to exclude the sender
        $socketId = request()->header('X-Socket-ID');

        broadcast(new ProjectDataChanged(
            projectId: $project->id,
            entityType: $entityType,
            action: $action,
            data: $data,
            entityId: $entity?->id,
            socketId: $socketId
        ))->toOthers();
    }
}
