<?php

declare(strict_types=1);

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Stage
 */
class StageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'HowToStep',
            'identifier' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'position' => $this->sort_order,
            'status' => [
                '@type' => 'ActionStatusType',
                'name' => $this->status,
            ],
            'requiresReleaseForm' => $this->requires_release_form,
            'location' => $this->when($this->relationLoaded('area'), function () {
                return [
                    '@type' => 'Place',
                    'identifier' => $this->area->id,
                    'name' => $this->area->name,
                ];
            }),
            'dateCreated' => $this->created_at?->format('c'),
            'dateModified' => $this->updated_at?->format('c'),
        ];
    }
}
