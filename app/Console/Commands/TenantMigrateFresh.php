<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class TenantMigrateFresh extends Command
{
    protected $signature = 'tenants:migrate-fresh {--seed : Seed the database after migration}';

    protected $description = 'Drop all tables and re-run migrations for landlord and all tenants';

    public function handle(): int
    {
        $this->warn('This will drop all databases and recreate them.');

        if (!$this->confirm('Are you sure you want to proceed?')) {
            return Command::FAILURE;
        }

        // Drop all tenant databases first
        $this->info('Dropping tenant databases...');
        $tenants = [];
        
        try {
            $tenants = Tenant::all();
            foreach ($tenants as $tenant) {
                $this->line("  Dropping database for tenant: {$tenant->name}");
                try {
                    DB::connection('central')->statement("DROP DATABASE IF EXISTS \"{$tenant->tenancy_db_name}\"");
                } catch (\Exception $e) {
                    $this->warn("  Could not drop database: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            $this->line('  No existing tenants table found.');
        }

        // Drop landlord tables
        $this->info('Dropping landlord tables...');
        Artisan::call('db:wipe', ['--database' => 'central', '--force' => true]);

        // Run landlord migrations
        $this->info('Running landlord migrations...');
        Artisan::call('migrate', [
            '--path' => 'database/migrations/landlord',
            '--database' => 'central',
            '--force' => true,
        ]);
        $this->line(Artisan::output());

        // Seed if requested
        if ($this->option('seed')) {
            $this->info('Seeding database...');
            Artisan::call('db:seed', ['--force' => true]);
            $this->line(Artisan::output());
        }

        $this->info('Done!');

        return Command::SUCCESS;
    }
}
