<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'message',
        'status',
        'processed_by',
        'created_user_id',
        'processed_at',
        'rejection_reason',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (RegistrationRequest $request) {
            $request->email = strtolower(trim($request->email));
        });
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function approve(User $processor, User $createdUser): void
    {
        $this->update([
            'status' => 'approved',
            'processed_by' => $processor->id,
            'created_user_id' => $createdUser->id,
            'processed_at' => now(),
        ]);
    }

    public function reject(User $processor, ?string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'processed_by' => $processor->id,
            'processed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
