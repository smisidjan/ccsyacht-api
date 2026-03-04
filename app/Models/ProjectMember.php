<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProjectMember model representing a user's membership in a project.
 *
 * @see https://schema.org/OrganizationRole
 */
class ProjectMember extends Model
{
    use HasUuids;

    protected $fillable = [
        'project_id',
        'user_id',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================================================
    // Schema.org Output
    // =========================================================================

    public function toSchemaOrg(): array
    {
        return [
            '@type' => 'OrganizationRole',
            'identifier' => $this->id,
            'roleName' => 'member',
            'member' => $this->user ? [
                '@type' => 'Person',
                'identifier' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null,
            'dateCreated' => $this->created_at?->format('c'),
        ];
    }
}
