<?php

namespace App\Http\Resources\Project;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class DocumentTypeResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            '@context' => $this->schemaContext(),
            '@type' => 'DefinedTerm',
            'identifier' => $this->id,
            'name' => $this->name,
            'isRequired' => $this->is_required,
            'position' => $this->sort_order,
            'documentCount' => $this->whenCounted('documents'),
            'dateCreated' => $this->formatDate($this->created_at),
            'dateModified' => $this->formatDate($this->updated_at),
        ];
    }
}
