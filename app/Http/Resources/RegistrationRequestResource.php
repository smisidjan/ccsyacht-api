<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class RegistrationRequestResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            '@context' => $this->schemaContext(),
            '@type' => 'RegisterAction',
            'identifier' => $this->id,
            'agent' => [
                '@type' => 'Person',
                'name' => $this->name,
                'email' => $this->email,
            ],
            'description' => $this->message,
            'actionStatus' => $this->mapActionStatus($this->status, self::$pendingStatusMapping),
            'dateCreated' => $this->formatDate($this->created_at),
            'dateProcessed' => $this->formatDate($this->processed_at),
            'processedBy' => $this->whenLoaded('processedBy', fn() => $this->formatPerson($this->processedBy)),
            'rejectionReason' => $this->rejection_reason,
        ];
    }
}
