<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Deck model representing a deck/level on a yacht.
 *
 * @see https://schema.org/Place
 */
class Deck extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'project_id',
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
     * The project this deck belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Areas on this deck.
     */
    public function areas(): HasMany
    {
        return $this->hasMany(Area::class)->orderBy('sort_order');
    }

    /**
     * All stages across all areas on this deck.
     */
    public function stages(): HasManyThrough
    {
        return $this->hasManyThrough(Stage::class, Area::class);
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

        return $data;
    }
}
