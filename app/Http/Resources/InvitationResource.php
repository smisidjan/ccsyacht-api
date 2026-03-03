<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Invitation;
use Illuminate\Http\Request;

class InvitationResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        /** @var Invitation $this */
        $data = [
            '@context' => $this->schemaContext(),
            '@type' => 'InviteAction',
            'identifier' => $this->id,
            'actionStatus' => $this->getSchemaOrgActionStatus(),
            'recipient' => [
                '@type' => 'Person',
                'email' => $this->email,
            ],
            'object' => $this->formatEmployeeRole(),
            'agent' => $this->whenLoaded('invitedBy', fn () => $this->formatPerson($this->invitedBy)),
            'dateCreated' => $this->formatDate($this->created_at),
            'expires' => $this->formatDate($this->expires_at),
            'dateAccepted' => $this->formatDate($this->accepted_at),
            'dateDeclined' => $this->formatDate($this->declined_at),
            'isExpired' => $this->isExpired(),
        ];

        return $data;
    }

    /**
     * Format the EmployeeRole object for the invitation.
     */
    protected function formatEmployeeRole(): array
    {
        /** @var Invitation $this */
        $role = [
            '@type' => 'EmployeeRole',
            'roleName' => $this->role,
            'employmentType' => $this->employment_type ?? 'employee',
        ];

        if ($this->named_position) {
            $role['namedPosition'] = $this->named_position;
        }

        // For guests, include home organization info
        if ($this->isGuestInvite()) {
            $homeOrgName = $this->getHomeOrganizationDisplayName();
            if ($homeOrgName) {
                $role['description'] = "Guest from: {$homeOrgName}";
            }
        }

        return $role;
    }

    /**
     * Get the Schema.org action status.
     */
    protected function getSchemaOrgActionStatus(): string
    {
        /** @var Invitation $this */
        return match ($this->status) {
            'pending' => $this->isExpired() ? 'FailedActionStatus' : 'PotentialActionStatus',
            'accepted' => 'CompletedActionStatus',
            'declined' => 'FailedActionStatus',
            'expired' => 'FailedActionStatus',
            default => 'PotentialActionStatus',
        };
    }

    /**
     * Detailed view for a single invitation.
     */
    public static function detailed(Invitation $invitation): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'InviteAction',
            'identifier' => $invitation->id,
            'actionStatus' => $invitation->isPending() ? 'PotentialActionStatus' : 'FailedActionStatus',
            'recipient' => [
                '@type' => 'Person',
                'email' => $invitation->email,
            ],
            'object' => [
                '@type' => 'EmployeeRole',
                'roleName' => $invitation->role,
                'employmentType' => $invitation->employment_type ?? 'employee',
            ],
            'isValid' => $invitation->isPending(),
            'isExpired' => $invitation->isExpired(),
            'expires' => $invitation->expires_at->toIso8601String(),
            'dateCreated' => $invitation->created_at->toIso8601String(),
        ];

        // Add named position if present
        if ($invitation->named_position) {
            $data['object']['namedPosition'] = $invitation->named_position;
        }

        // Add inviter info
        if ($invitation->invitedBy) {
            $data['agent'] = [
                '@type' => 'Person',
                'identifier' => $invitation->invitedBy->id,
                'name' => $invitation->invitedBy->name,
            ];
        }

        // For guests, add home organization info
        if ($invitation->isGuestInvite()) {
            $homeOrgName = $invitation->getHomeOrganizationDisplayName();
            if ($homeOrgName) {
                $data['object']['description'] = "Guest from: {$homeOrgName}";
            }
        }

        return $data;
    }
}
