<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasUuids, HasFactory<\Database\Factories\UserFactory>, Notifiable, HasApiTokens, HasRoles */
    use HasUuids, HasFactory, Notifiable, HasApiTokens, HasRoles;

    protected static function booted(): void
    {
        // Sync user to central tenant_users table for email lookup
        static::created(function (User $user) {
            if ($tenant = tenant()) {
                TenantUser::updateOrCreate(
                    ['email' => $user->email, 'tenant_id' => $tenant->id],
                    ['user_id' => $user->id]
                );
            }
        });

        static::updated(function (User $user) {
            if ($user->isDirty('email') && $tenant = tenant()) {
                TenantUser::where('user_id', $user->id)
                    ->where('tenant_id', $tenant->id)
                    ->update(['email' => $user->email]);
            }
        });

        static::deleted(function (User $user) {
            if ($tenant = tenant()) {
                TenantUser::where('user_id', $user->id)
                    ->where('tenant_id', $tenant->id)
                    ->delete();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    public function processedRegistrationRequests(): HasMany
    {
        return $this->hasMany(RegistrationRequest::class, 'processed_by');
    }
}
