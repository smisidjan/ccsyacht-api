<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * Area model representing a specific area within a deck.
 *
 * @see https://schema.org/Place
 */
class Area extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'deck_id',
        'name',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * The deck this area belongs to.
     */
    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class);
    }

    /**
     * Get the project through the deck.
     */
    public function project(): HasOneThrough
    {
        return $this->hasOneThrough(
            Project::class,
            Deck::class,
            'id',
            'id',
            'deck_id',
            'project_id'
        );
    }

    /**
     * Stages in this area.
     */
    public function stages(): HasMany
    {
        return $this->hasMany(Stage::class)->orderBy('sort_order');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // =========================================================================
    // Schema.org Output
    // =========================================================================

    /**
     * Convert to Schema.org Place JSON-LD format.
     *
     * @see https://schema.org/Place
     */
    public function toSchemaOrg(): array
    {
        $data = [
            '@type' => 'Place',
            'identifier' => $this->id,
            'name' => $this->name,
            'position' => $this->sort_order,
            'dateCreated' => $this->created_at?->format('c'),
            'dateModified' => $this->updated_at?->format('c'),
        ];

        if ($this->description) {
            $data['description'] = $this->description;
        }

        if ($this->deck) {
            $data['containedInPlace'] = [
                '@type' => 'Place',
                'identifier' => $this->deck->id,
                'name' => $this->deck->name,
            ];
        }

        return $data;
    }
}
