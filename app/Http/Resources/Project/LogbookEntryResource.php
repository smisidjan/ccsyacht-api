<?php

declare(strict_types=1);

namespace App\Http\Resources\Project;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin \App\Models\LogbookEntry
 */
class LogbookEntryResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            '@context' => $this->schemaContext(),
            '@type' => 'Action',
            'identifier' => $this->id,
            'actionStatus' => 'CompletedActionStatus',
            'name' => $this->action_type,
            'description' => $this->description,
            'agent' => $this->getAgent(),
            'additionalProperty' => $this->when($this->metadata, $this->metadata),
            'startTime' => $this->formatDate($this->created_at),
        ];
    }

    /**
     * Get the agent for this action (user or system admin).
     */
    private function getAgent(): ?array
    {
        // If there's a user relation loaded and present, use that
        if ($this->relationLoaded('user') && $this->user) {
            return [
                '@type' => 'Person',
                'identifier' => $this->user->id,
                'name' => $this->user->name,
            ];
        }

        // If there's an actor_name (system admin), use that
        if ($this->actor_name) {
            return [
                '@type' => 'Organization',
                'name' => $this->actor_name,
            ];
        }

        return null;
    }
}
