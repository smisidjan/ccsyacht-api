<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TenantRegistrationToken extends Model
{
    protected $fillable = [
        'email',
        'token',
        'role',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (TenantRegistrationToken $model) {
            if (empty($model->token)) {
                $tenantSlug = tenant('slug');
                $model->token = "{$tenantSlug}:" . Str::random(64);
            }
            if (empty($model->expires_at)) {
                $model->expires_at = now()->addDays(7);
            }
        });
    }

    public static function findByToken(string $token): ?self
    {
        if (!str_contains($token, ':')) {
            return null;
        }

        [$tenantSlug, $actualToken] = explode(':', $token, 2);

        $tenant = Tenant::where('slug', $tenantSlug)
            ->where('active', true)
            ->first();

        if (!$tenant) {
            return null;
        }

        tenancy()->initialize($tenant);

        return static::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
