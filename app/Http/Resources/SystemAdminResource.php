<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SystemAdminResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            '@context' => $this->schemaContext(),
            '@type' => 'Person',
            'identifier' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'isActive' => $this->active,
            'dateCreated' => $this->formatDate($this->created_at),
        ];
    }
}
