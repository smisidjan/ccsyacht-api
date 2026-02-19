<?php

namespace Database\Seeders;

use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        // Create admin user if tenant has admin data
        $tenant = tenant();
        $adminEmail = $tenant->admin_email ?? null;
        $adminPassword = $tenant->admin_password ?? null;
        $adminName = $tenant->admin_name ?? 'Admin';

        if ($adminEmail && $adminPassword) {
            $admin = User::create([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => $adminPassword,
                'email_verified_at' => now(),
                'active' => true,
            ]);

            $admin->assignRole('admin');

            // Sync to central tenant_users table
            TenantUser::updateOrCreate(
                ['email' => $admin->email, 'tenant_id' => $tenant->id],
                ['user_id' => $admin->id]
            );

            // Clear sensitive data from tenant after use
            $tenant->update([
                'admin_email' => null,
                'admin_password' => null,
                'admin_name' => null,
            ]);
        }
    }
}
