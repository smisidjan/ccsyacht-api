<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'identifier' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'emailVerified' => $this->email_verified_at !== null,
            'active' => $this->active,
            'dateCreated' => $this->created_at?->toIso8601String(),
            'dateModified' => $this->updated_at?->toIso8601String(),
            'roles' => $this->getRoleNames(),
        ];

        // Include tenant context if available
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
