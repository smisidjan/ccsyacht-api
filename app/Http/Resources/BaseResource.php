<?php

namespace App\Http\Resources;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    protected function schemaContext(): string
    {
        return 'https://schema.org';
    }

    protected function formatDate(?DateTimeInterface $date): ?string
    {
        return $date?->format('c');
    }

    protected function formatPerson(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            '@type' => 'Person',
            'identifier' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    protected function mapActionStatus(string $status, array $mapping): string
    {
        return $mapping[$status] ?? 'PotentialActionStatus';
    }

    protected static array $pendingStatusMapping = [
        'pending' => 'PotentialActionStatus',
        'approved' => 'CompletedActionStatus',
        'accepted' => 'CompletedActionStatus',
        'rejected' => 'FailedActionStatus',
        'declined' => 'FailedActionStatus',
        'expired' => 'FailedActionStatus',
    ];
}
