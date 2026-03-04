<?php

namespace App\Http\Resources\Project;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class DocumentTypeResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            '@context' => $this->schemaContext(),
            '@type' => 'DefinedTerm',
            'identifier' => $this->id,
            'name' => $this->name,
            'isRequired' => $this->is_required,
            'position' => $this->sort_order,
            'dateCreated' => $this->formatDate($this->created_at),
            'dateModified' => $this->formatDate($this->updated_at),
        ];

        // Include document count if loaded
        if ($this->relationLoaded('documents')) {
            $data['documentCount'] = $this->documents->count();
        }

        return $data;
    }
}
