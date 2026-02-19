<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultTenantSeeder extends Seeder
{
    public function run(): void
    {
        // Check if tenant already exists
        $existingTenant = Tenant::where('slug', 'ccs-yacht')->first();

        if ($existingTenant) {
            $tenant = $existingTenant;
            $this->command->info('Default tenant already exists.');
        } else {
            // Create default tenant (this triggers database creation, migration, and seeding via TenancyServiceProvider events)
            $this->command->info('Creating default tenant: CCS Yacht');
            $tenant = Tenant::create([
                'name' => 'CCS Yacht',
                'slug' => 'ccs-yacht',
                'active' => true,
            ]);
            $this->command->info('Tenant database created and migrated.');
        }

        // Create admin user within the tenant context
        $this->command->info('Creating tenant admin user...');
        $tenant->run(function () use ($tenant) {
            $admin = User::firstOrCreate(
                ['email' => 'admin@ccsyacht.nl'],
                [
                    'name' => 'Admin',
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'active' => true,
                ]
            );

            if (!$admin->hasRole('admin')) {
                $admin->assignRole('admin');
            }

            // Ensure user is synced to central tenant_users table for email lookup
            TenantUser::updateOrCreate(
                ['email' => $admin->email, 'tenant_id' => $tenant->id],
                ['user_id' => $admin->id]
            );
        });
        $this->command->info('Tenant admin user created: admin@ccsyacht.nl');
    }
}
