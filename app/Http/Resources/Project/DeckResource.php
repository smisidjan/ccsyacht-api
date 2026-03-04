<?php

declare(strict_types=1);

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Deck
 */
class DeckResource extends JsonResource
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
            'areaCount' => $this->whenCounted('areas'),
            'stageCount' => $this->whenCounted('stages'),
            'containedInPlace' => $this->when($this->relationLoaded('project'), function () {
                return [
                    '@type' => 'CreativeWork',
                    'identifier' => $this->project->id,
                    'name' => $this->project->name,
                ];
            }),
            'containsPlace' => $this->when($this->relationLoaded('areas'), function () {
                return AreaResource::collection($this->areas);
            }),
            'dateCreated' => $this->created_at?->format('c'),
            'dateModified' => $this->updated_at?->format('c'),
        ];
    }
}
