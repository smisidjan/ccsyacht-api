<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class SystemAdmin extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $connection = 'central';

    protected $fillable = [
        'name',
        'email',
        'password',
        'active',
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

        static::creating(function (SystemAdmin $admin) {
            $admin->email = strtolower($admin->email);
        });

        static::updating(function (SystemAdmin $admin) {
            if ($admin->isDirty('email')) {
                $admin->email = strtolower($admin->email);
            }
        });
    }
}
