<?php

namespace Database\Seeders;

use App\Models\TenantRegistrationToken;
use App\Notifications\TenantAdminRegistrationNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        // Send registration invitation if tenant has admin email
        $tenant = tenant();
        $adminEmail = $tenant->admin_email ?? null;

        if ($adminEmail) {
            $token = TenantRegistrationToken::create([
                'email' => $adminEmail,
                'role' => 'admin',
            ]);

            // Send registration email
            Notification::route('mail', $adminEmail)
                ->notify(new TenantAdminRegistrationNotification($token, $tenant));

            // Clear admin data from tenant after use
            $tenant->update([
                'admin_email' => null,
                'admin_name' => null,
            ]);
        }
    }
}
