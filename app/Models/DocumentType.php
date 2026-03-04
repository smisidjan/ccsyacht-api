<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * DocumentType model representing a DefinedTerm for document categorization.
 *
 * @see https://schema.org/DefinedTerm
 */
class DocumentType extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * The project this document type belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Documents of this type.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // =========================================================================
    // Schema.org Output
    // =========================================================================

    /**
     * Convert to Schema.org DefinedTerm JSON-LD format.
     *
     * @see https://schema.org/DefinedTerm
     */
    public function toSchemaOrg(): array
    {
        return [
            '@type' => 'DefinedTerm',
            'identifier' => $this->id,
            'name' => $this->name,
            'isRequired' => $this->is_required,
            'position' => $this->sort_order,
        ];
    }
}
