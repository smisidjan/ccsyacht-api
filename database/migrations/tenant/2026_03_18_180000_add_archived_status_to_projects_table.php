<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL requires raw SQL to modify enum types
        // Drop the check constraint and recreate with new values
        // Removes 'locked', adds 'archived'
        DB::statement("ALTER TABLE projects DROP CONSTRAINT IF EXISTS projects_status_check");
        DB::statement("ALTER TABLE projects ADD CONSTRAINT projects_status_check CHECK (status::text = ANY (ARRAY['setup'::text, 'active'::text, 'completed'::text, 'archived'::text]))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE projects DROP CONSTRAINT IF EXISTS projects_status_check");
        DB::statement("ALTER TABLE projects ADD CONSTRAINT projects_status_check CHECK (status::text = ANY (ARRAY['setup'::text, 'active'::text, 'locked'::text, 'completed'::text]))");
    }
};
