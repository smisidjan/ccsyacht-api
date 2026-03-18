<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LogbookEntry model representing an activity log entry.
 *
 * @see https://schema.org/Action
 */
class LogbookEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'project_id',
        'user_id',
        'actor_name',
        'action_type',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

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
    // Factory Methods
    // =========================================================================

    public static function log(
        Project $project,
        string $actionType,
        string $description,
        ?User $user = null,
        ?array $metadata = null,
        ?string $actorName = null
    ): self {
        return self::create([
            'project_id' => $project->id,
            'user_id' => $user?->id,
            'actor_name' => $actorName ?? $user?->name,
            'action_type' => $actionType,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log an action by a system admin (no user record in tenant database).
     */
    public static function logSystemAdmin(
        Project $project,
        string $actionType,
        string $description,
        ?array $metadata = null
    ): self {
        return self::log(
            $project,
            $actionType,
            $description,
            null,
            $metadata,
            config('app.system_admin_actor_name')
        );
    }

    // =========================================================================
    // Schema.org Output
    // =========================================================================

    public function toSchemaOrg(): array
    {
        $data = [
            '@type' => 'Action',
            'identifier' => $this->id,
            'actionStatus' => 'CompletedActionStatus',
            'name' => $this->action_type,
            'description' => $this->description,
            'startTime' => $this->created_at?->format('c'),
        ];

        if ($this->user) {
            $data['agent'] = [
                '@type' => 'Person',
                'identifier' => $this->user->id,
                'name' => $this->user->name,
            ];
        } elseif ($this->actor_name) {
            $data['agent'] = [
                '@type' => 'Organization',
                'name' => $this->actor_name,
            ];
        }

        if ($this->metadata) {
            $data['additionalProperty'] = $this->metadata;
        }

        return $data;
    }
}
