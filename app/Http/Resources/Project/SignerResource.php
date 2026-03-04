<?php

declare(strict_types=1);

namespace App\Http\Resources\Project;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin \App\Models\ProjectSigner
 */
class SignerResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            '@context' => $this->schemaContext(),
            '@type' => 'OrganizationRole',
            'identifier' => $this->id,
            'roleName' => 'signer',
            'member' => $this->when($this->relationLoaded('user') && $this->user, function () {
                return [
                    '@type' => 'Person',
                    'identifier' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'dateCreated' => $this->formatDate($this->created_at),
        ];
    }
}
