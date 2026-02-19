<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'RegisterAction',
            'identifier' => $this->id,
            'agent' => [
                '@type' => 'Person',
                'name' => $this->name,
                'email' => $this->email,
            ],
            'description' => $this->message,
            'actionStatus' => $this->getSchemaActionStatus(),
            'dateCreated' => $this->created_at?->toIso8601String(),
            'dateProcessed' => $this->processed_at?->toIso8601String(),
            'processedBy' => $this->whenLoaded('processedBy', function () {
                return [
                    '@type' => 'Person',
                    'identifier' => $this->processedBy->id,
                    'name' => $this->processedBy->name,
                    'email' => $this->processedBy->email,
                ];
            }),
            'rejectionReason' => $this->rejection_reason,
        ];
    }

    private function getSchemaActionStatus(): string
    {
        return match ($this->status) {
            'pending' => 'PotentialActionStatus',
            'approved' => 'CompletedActionStatus',
            'rejected' => 'FailedActionStatus',
            default => 'PotentialActionStatus',
        };
    }
}
