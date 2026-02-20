<?php

namespace App\Http\Resources;

use App\Models\Invitation;
use Illuminate\Http\Request;

class InvitationResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            '@context' => $this->schemaContext(),
            '@type' => 'InviteAction',
            'identifier' => $this->id,
            'recipient' => [
                '@type' => 'Person',
                'email' => $this->email,
            ],
            'agent' => $this->whenLoaded('invitedBy', fn() => $this->formatPerson($this->invitedBy)),
            'actionStatus' => $this->mapActionStatus($this->status, self::$pendingStatusMapping),
            'role' => $this->role,
            'dateCreated' => $this->formatDate($this->created_at),
            'expires' => $this->formatDate($this->expires_at),
            'dateAccepted' => $this->formatDate($this->accepted_at),
            'dateDeclined' => $this->formatDate($this->declined_at),
            'isExpired' => $this->isExpired(),
        ];
    }

    public static function detailed(Invitation $invitation): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'InviteAction',
            'identifier' => $invitation->id,
            'recipient' => [
                '@type' => 'Person',
                'email' => $invitation->email,
            ],
            'role' => $invitation->role,
            'actionStatus' => $invitation->isPending() ? 'PotentialActionStatus' : 'FailedActionStatus',
            'isValid' => $invitation->isPending(),
            'isExpired' => $invitation->isExpired(),
            'expires' => $invitation->expires_at->toIso8601String(),
            'invitedBy' => $invitation->invitedBy->name,
        ];
    }
}
