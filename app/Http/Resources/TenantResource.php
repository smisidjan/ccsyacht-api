<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TenantResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            '@context' => $this->schemaContext(),
            '@type' => 'Organization',
            'identifier' => $this->id,
            'name' => $this->name,
            'alternateName' => $this->slug,
            'isActive' => $this->active,
            'dateCreated' => $this->formatDate($this->created_at),
            'dateModified' => $this->formatDate($this->updated_at),
        ];
    }
}
