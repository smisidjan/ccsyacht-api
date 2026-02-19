<?php

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $connection = 'central';

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'active',
            'created_at',
            'updated_at',
        ];
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'data' => 'array',
        ];
    }
}
