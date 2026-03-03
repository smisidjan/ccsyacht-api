<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AuthResource extends BaseResource
{
    private string $token;

    public function __construct($resource, string $token)
    {
        parent::__construct($resource);
        $this->token = $token;
    }

    public function toArray(Request $request): array
    {
        $person = [
            '@type' => 'Person',
            'identifier' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->getRoleNames(),
            'employmentType' => $this->employment_type,
        ];

        // Add guest-specific information
        if ($this->isGuest()) {
            $homeOrgName = $this->getHomeOrganizationDisplayName();
            if ($homeOrgName) {
                $person['homeOrganization'] = [
                    '@type' => 'Organization',
                    'name' => $homeOrgName,
                ];
                if ($this->home_organization_id) {
                    $person['homeOrganization']['identifier'] = $this->home_organization_id;
                }
            }
        }

        return [
            '@context' => $this->schemaContext(),
            '@type' => 'AuthorizeAction',
            'actionStatus' => 'CompletedActionStatus',
            'result' => $person,
            'token' => $this->token,
            'tokenType' => 'Bearer',
        ];
    }
}
