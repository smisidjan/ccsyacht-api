<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            '@context' => $this->schemaContext(),
            '@type' => 'Person',
            'identifier' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'emailVerified' => $this->email_verified_at !== null,
            'active' => $this->active,
            'dateCreated' => $this->formatDate($this->created_at),
            'dateModified' => $this->formatDate($this->updated_at),
            'roles' => $this->getRoleNames(),
        ];

        if ($tenant = tenant()) {
            $data['memberOf'] = [
                '@type' => 'Organization',
                'identifier' => $tenant->id,
                'name' => $tenant->name,
            ];
        }

        return $data;
    }
}
