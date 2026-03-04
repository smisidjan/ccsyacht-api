<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * Stage model representing a painting process step within an area.
 *
 * @see https://schema.org/HowToStep
 */
class Stage extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'area_id',
        'name',
        'description',
        'status',
        'requires_release_form',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_release_form' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * The area this stage belongs to.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Get the deck through the area.
     */
    public function deck(): HasOneThrough
    {
        return $this->hasOneThrough(
            Deck::class,
            Area::class,
            'id',
            'id',
            'area_id',
            'deck_id'
        );
    }

    // =========================================================================
    // Status Helpers
    // =========================================================================

    public function isNotStarted(): bool
    {
        return $this->status === 'not_started';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeNotStarted($query)
    {
        return $query->where('status', 'not_started');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // =========================================================================
    // Schema.org Output
    // =========================================================================

    /**
     * Convert to Schema.org HowToStep JSON-LD format.
     *
     * @see https://schema.org/HowToStep
     */
    public function toSchemaOrg(): array
    {
        $data = [
            '@type' => 'HowToStep',
            'identifier' => $this->id,
            'name' => $this->name,
            'position' => $this->sort_order,
            'status' => [
                '@type' => 'ActionStatusType',
                'name' => $this->status,
            ],
            'requiresReleaseForm' => $this->requires_release_form,
            'dateCreated' => $this->created_at?->format('c'),
            'dateModified' => $this->updated_at?->format('c'),
        ];

        if ($this->description) {
            $data['itemListElement'] = [
                '@type' => 'HowToDirection',
                'text' => $this->description,
            ];
        }

        if ($this->area) {
            $data['location'] = [
                '@type' => 'Place',
                'identifier' => $this->area->id,
                'name' => $this->area->name,
            ];
        }

        return $data;
    }
}
