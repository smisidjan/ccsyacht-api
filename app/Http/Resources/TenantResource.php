<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TenantResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            '@context' => $this->schemaContext(),
            '@type' => 'Organization',
            'identifier' => $this->id,
            'name' => $this->name,
            'alternateName' => $this->slug,
            'isActive' => $this->active,
            'restrictedPermissions' => $this->restricted_permissions,
            'dateCreated' => $this->formatDate($this->created_at),
            'dateModified' => $this->formatDate($this->updated_at),
        ];

        // Include subscription as Schema.org Offer if loaded
        if ($this->relationLoaded('subscription') && $this->subscription) {
            $data['makesOffer'] = $this->subscription->toSchemaOrg();
        }

        return $data;
    }
}
