<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Project model representing a yacht painting project (CreativeWork).
 *
 * @see https://schema.org/CreativeWork
 */
class Project extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'shipyard_id',
        'name',
        'description',
        'project_type',
        'status',
        'start_date',
        'end_date',
        'general_arrangement_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Project $project) {
            if (empty($project->status)) {
                $project->status = 'setup';
            }
        });
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * The shipyard where this project takes place.
     */
    public function shipyard(): BelongsTo
    {
        return $this->belongsTo(Shipyard::class);
    }

    /**
     * The user who created this project.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Document types for this project.
     */
    public function documentTypes(): HasMany
    {
        return $this->hasMany(DocumentType::class);
    }

    /**
     * Decks for this project.
     */
    public function decks(): HasMany
    {
        return $this->hasMany(Deck::class)->orderBy('sort_order');
    }

    /**
     * Project members.
     */
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /**
     * Default signers for this project.
     */
    public function signers(): HasMany
    {
        return $this->hasMany(ProjectSigner::class);
    }

    /**
     * Logbook entries for this project.
     */
    public function logbookEntries(): HasMany
    {
        return $this->hasMany(LogbookEntry::class);
    }

    // =========================================================================
    // Status Helpers
    // =========================================================================

    public function isSetup(): bool
    {
        return $this->status === 'setup';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeSetup($query)
    {
        return $query->where('status', 'setup');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLocked($query)
    {
        return $query->where('status', 'locked');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeNewBuilt($query)
    {
        return $query->where('project_type', 'new_built');
    }

    public function scopeRefit($query)
    {
        return $query->where('project_type', 'refit');
    }

    /**
     * Scope to filter projects based on user's employment type.
     * Guests only see projects where they are a member.
     * Employees see all projects.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->employment_type !== 'guest') {
            return $query;
        }

        return $query->whereHas('members', function (Builder $q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    // =========================================================================
    // Schema.org Output
    // =========================================================================

    /**
     * Convert to Schema.org CreativeWork JSON-LD format.
     *
     * @see https://schema.org/CreativeWork
     */
    public function toSchemaOrg(): array
    {
        $data = [
            '@type' => 'CreativeWork',
            'identifier' => $this->id,
            'name' => $this->name,
            'additionalType' => $this->project_type,
            'status' => $this->status,
            'dateCreated' => $this->created_at?->format('c'),
            'dateModified' => $this->updated_at?->format('c'),
        ];

        if ($this->description) {
            $data['description'] = $this->description;
        }

        if ($this->start_date) {
            $data['startDate'] = $this->start_date->format('Y-m-d');
        }

        if ($this->end_date) {
            $data['endDate'] = $this->end_date->format('Y-m-d');
        }

        if ($this->shipyard) {
            $data['producer'] = $this->shipyard->toSchemaOrg();
        }

        if ($this->creator) {
            $data['author'] = [
                '@type' => 'Person',
                'identifier' => $this->creator->id,
                'name' => $this->creator->name,
            ];
        }

        return $data;
    }
}
