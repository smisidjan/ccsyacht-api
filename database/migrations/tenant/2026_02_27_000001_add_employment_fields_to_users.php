<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role_name', 50)->default('user')->after('active');
            $table->string('named_position', 255)->nullable()->after('role_name');
            $table->string('employment_type', 20)->default('employee')->after('named_position');
            $table->string('home_organization_id')->nullable()->after('employment_type');
            $table->string('home_organization_name', 255)->nullable()->after('home_organization_id');

            $table->index('employment_type');
            $table->index('role_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['employment_type']);
            $table->dropIndex(['role_name']);
            $table->dropColumn([
                'role_name',
                'named_position',
                'employment_type',
                'home_organization_id',
                'home_organization_name',
            ]);
        });
    }
};
