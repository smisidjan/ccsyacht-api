<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'identifier' => $this->id,
            'name' => $this->name,
            'alternateName' => $this->slug,
            'isActive' => $this->active,
            'dateCreated' => $this->created_at?->toIso8601String(),
            'dateModified' => $this->updated_at?->toIso8601String(),
        ];
    }
}
