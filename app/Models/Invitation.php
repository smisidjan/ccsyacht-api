<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'role',
        'status',
        'invited_by',
        'accepted_user_id',
        'expires_at',
        'accepted_at',
        'declined_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Invitation $invitation) {
            if (empty($invitation->token)) {
                $tenantSlug = tenant('slug');
                $randomToken = Str::random(64);
                $invitation->token = "{$tenantSlug}:{$randomToken}";
            }
            if (empty($invitation->expires_at)) {
                $invitation->expires_at = now()->addDays(7);
            }
        });
    }

    /**
     * Find an invitation by its token, initializing the correct tenant context.
     * Token format: {tenant_slug}:{random_token}
     */
    public static function findByToken(string $token): ?self
    {
        if (!str_contains($token, ':')) {
            return null;
        }

        [$tenantSlug, $actualToken] = explode(':', $token, 2);

        $tenant = \App\Models\Tenant::where('slug', $tenantSlug)
            ->where('active', true)
            ->first();

        if (!$tenant) {
            return null;
        }

        tenancy()->initialize($tenant);

        return static::where('token', $token)->first();
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function acceptedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    public function markAsAccepted(User $user): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_user_id' => $user->id,
            'accepted_at' => now(),
        ]);
    }

    public function markAsDeclined(): void
    {
        $this->update([
            'status' => 'declined',
            'declined_at' => now(),
        ]);
    }
}
