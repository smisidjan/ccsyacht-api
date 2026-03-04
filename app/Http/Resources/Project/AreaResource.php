<?php

declare(strict_types=1);

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Area
 */
class AreaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Place',
            'identifier' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'position' => $this->sort_order,
            'stageCount' => $this->whenCounted('stages'),
            'containedInPlace' => $this->when($this->relationLoaded('deck'), function () {
                return [
                    '@type' => 'Place',
                    'identifier' => $this->deck->id,
                    'name' => $this->deck->name,
                ];
            }),
            'containsPlace' => $this->when($this->relationLoaded('stages'), function () {
                return StageResource::collection($this->stages);
            }),
            'dateCreated' => $this->created_at?->format('c'),
            'dateModified' => $this->updated_at?->format('c'),
        ];
    }
}
