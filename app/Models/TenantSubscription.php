<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSubscription extends Model
{
    use HasUuids;

    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'max_projects',
        'max_users',
        'status',
        'created_by',
    ];

    protected $casts = [
        'max_projects' => 'integer',
        'max_users' => 'integer',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(SystemAdmin::class, 'created_by');
    }

    // =========================================================================
    // Status Helpers
    // =========================================================================

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasUnlimitedProjects(): bool
    {
        return $this->max_projects === null;
    }

    public function hasUnlimitedUsers(): bool
    {
        return $this->max_users === null;
    }

    // =========================================================================
    // Schema.org Output
    // =========================================================================

    /**
     * Convert to Schema.org Offer JSON-LD format.
     *
     * @see https://schema.org/Offer
     */
    public function toSchemaOrg(): array
    {
        $quantities = [];

        if ($this->max_projects !== null) {
            $quantities[] = [
                '@type' => 'QuantitativeValue',
                'name' => 'projects',
                'maxValue' => $this->max_projects,
            ];
        }

        if ($this->max_users !== null) {
            $quantities[] = [
                '@type' => 'QuantitativeValue',
                'name' => 'users',
                'maxValue' => $this->max_users,
            ];
        }

        return [
            '@type' => 'Offer',
            'identifier' => $this->id,
            'offerStatus' => $this->isActive()
                ? 'https://schema.org/OfferAvailable'
                : 'https://schema.org/OfferExpired',
            'eligibleQuantity' => $quantities,
        ];
    }
}
