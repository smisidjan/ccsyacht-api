<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shipyard model representing an Organization with ContactPoint.
 *
 * @see https://schema.org/Organization
 * @see https://schema.org/ContactPoint
 */
class Shipyard extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'address',
    ];

    /**
     * Projects at this shipyard.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Convert to Schema.org Organization JSON-LD format.
     */
    public function toSchemaOrg(): array
    {
        $data = [
            '@type' => 'Organization',
            'identifier' => $this->id,
            'name' => $this->name,
        ];

        if ($this->address) {
            $data['address'] = $this->address;
        }

        if ($this->contact_name || $this->contact_email || $this->contact_phone) {
            $contactPoint = ['@type' => 'ContactPoint'];

            if ($this->contact_name) {
                $contactPoint['name'] = $this->contact_name;
            }
            if ($this->contact_email) {
                $contactPoint['email'] = $this->contact_email;
            }
            if ($this->contact_phone) {
                $contactPoint['telephone'] = $this->contact_phone;
            }

            $data['contactPoint'] = $contactPoint;
        }

        return $data;
    }
}
