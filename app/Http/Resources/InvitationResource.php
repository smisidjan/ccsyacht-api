<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'InviteAction',
            'identifier' => $this->id,
            'recipient' => [
                '@type' => 'Person',
                'email' => $this->email,
            ],
            'agent' => $this->whenLoaded('invitedBy', function () {
                return [
                    '@type' => 'Person',
                    'identifier' => $this->invitedBy->id,
                    'name' => $this->invitedBy->name,
                    'email' => $this->invitedBy->email,
                ];
            }),
            'actionStatus' => $this->getSchemaActionStatus(),
            'role' => $this->role,
            'dateCreated' => $this->created_at?->toIso8601String(),
            'expires' => $this->expires_at?->toIso8601String(),
            'dateAccepted' => $this->accepted_at?->toIso8601String(),
            'dateDeclined' => $this->declined_at?->toIso8601String(),
            'isExpired' => $this->isExpired(),
        ];
    }

    private function getSchemaActionStatus(): string
    {
        return match ($this->status) {
            'pending' => 'PotentialActionStatus',
            'accepted' => 'CompletedActionStatus',
            'declined' => 'FailedActionStatus',
            'expired' => 'FailedActionStatus',
            default => 'PotentialActionStatus',
        };
    }
}
