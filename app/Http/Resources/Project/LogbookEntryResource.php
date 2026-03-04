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
            'agent' => $this->when($this->relationLoaded('user') && $this->user, function () {
                return [
                    '@type' => 'Person',
                    'identifier' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
            'additionalProperty' => $this->when($this->metadata, $this->metadata),
            'startTime' => $this->formatDate($this->created_at),
        ];
    }
}
