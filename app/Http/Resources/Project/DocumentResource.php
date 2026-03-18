<?php

namespace App\Http\Resources\Project;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class DocumentResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            '@context' => $this->schemaContext(),
            '@type' => 'DigitalDocument',
            'identifier' => $this->id,
            'name' => $this->title,
            'fileName' => $this->file_name,
            'encodingFormat' => $this->mime_type,
            'contentSize' => $this->getFileSizeForHumans(),
            'contentSizeBytes' => $this->file_size,
            'dateCreated' => $this->formatDate($this->created_at),
            'dateModified' => $this->formatDate($this->updated_at),
        ];

        if ($this->description) {
            $data['description'] = $this->description;
        }

        if ($this->relationLoaded('uploader') && $this->uploader) {
            $data['author'] = $this->formatPerson($this->uploader);
        } elseif ($this->uploaded_by_name) {
            $data['author'] = [
                '@type' => 'Organization',
                'name' => $this->uploaded_by_name,
            ];
        }

        if ($this->relationLoaded('documentType') && $this->documentType) {
            $data['category'] = [
                '@type' => 'DefinedTerm',
                'identifier' => $this->documentType->id,
                'name' => $this->documentType->name,
            ];
        }

        return $data;
    }
}
