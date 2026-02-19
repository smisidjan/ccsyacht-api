<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    private string $token;

    public function __construct($resource, string $token)
    {
        parent::__construct($resource);
        $this->token = $token;
    }

    public function toArray(Request $request): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'AuthorizeAction',
            'actionStatus' => 'CompletedActionStatus',
            'result' => [
                '@type' => 'Person',
                'identifier' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'roles' => $this->getRoleNames(),
            ],
            'token' => $this->token,
            'tokenType' => 'Bearer',
        ];
    }
}
