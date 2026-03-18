<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Make created_by nullable for system admin created projects
            $table->uuid('created_by')->nullable()->change();

            // Add created_by_name for system admin attribution
            $table->string('created_by_name')->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('created_by_name');
            $table->uuid('created_by')->nullable(false)->change();
        });
    }
};
