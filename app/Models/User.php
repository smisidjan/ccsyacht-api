<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Tenant User model representing a Person with an EmployeeRole.
 *
 * Each user belongs to one tenant (organization) and has role/employment info.
 *
 * @see https://schema.org/Person
 * @see https://schema.org/EmployeeRole
 */
class User extends Authenticatable
{
    use HasUuids, HasFactory, Notifiable, HasApiTokens, HasRoles;

    // No $connection specified - uses tenant connection via Stancl/tenancy

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'active',
        'role_name',
        'named_position',
        'employment_type',
        'home_organization_id',
        'home_organization_name',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user) {
            if (empty($user->role_name)) {
                $user->role_name = 'user';
            }
            if (empty($user->employment_type)) {
                $user->employment_type = 'employee';
            }
        });
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Invitations sent by this user.
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    /**
     * For guests: the organization they come from (cross-database to central).
     */
    public function homeOrganization(): BelongsTo
    {
        return $this->setConnection('central')
            ->belongsTo(Tenant::class, 'home_organization_id');
    }

    // =========================================================================
    // Employment Type Helpers
    // =========================================================================

    /**
     * Check if user is an employee (works for this organization).
     */
    public function isEmployee(): bool
    {
        return $this->employment_type === 'employee';
    }

    /**
     * Check if user is a guest (visitor from another organization).
     */
    public function isGuest(): bool
    {
        return $this->employment_type === 'guest';
    }

    /**
     * Get the home organization display name (from relation or free text).
     */
    public function getHomeOrganizationDisplayName(): ?string
    {
        if ($this->home_organization_id) {
            // Try to get from central tenants table
            $tenant = Tenant::on('central')->find($this->home_organization_id);
            if ($tenant) {
                return $tenant->name;
            }
        }

        return $this->home_organization_name;
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeEmployees($query)
    {
        return $query->where('employment_type', 'employee');
    }

    public function scopeGuests($query)
    {
        return $query->where('employment_type', 'guest');
    }

    // =========================================================================
    // Schema.org Output
    // =========================================================================

    /**
     * Convert to Schema.org Person JSON-LD format with EmployeeRole.
     *
     * @see https://schema.org/Person
     */
    public function toSchemaOrg(): array
    {
        $data = [
            '@type' => 'Person',
            'identifier' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'worksFor' => [
                '@type' => 'EmployeeRole',
                'roleName' => $this->role_name,
                'employmentType' => $this->employment_type,
            ],
        ];

        if ($this->named_position) {
            $data['worksFor']['namedPosition'] = $this->named_position;
        }

        if ($this->isGuest()) {
            $homeOrgName = $this->getHomeOrganizationDisplayName();
            if ($homeOrgName) {
                $data['worksFor']['description'] = "Guest from: {$homeOrgName}";
            }
        }

        return $data;
    }
}
