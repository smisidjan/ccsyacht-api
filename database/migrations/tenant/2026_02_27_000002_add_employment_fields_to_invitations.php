<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->string('employment_type', 20)->default('employee')->after('role');
            $table->string('home_organization_id')->nullable()->after('employment_type');
            $table->string('home_organization_name', 255)->nullable()->after('home_organization_id');
            $table->string('named_position', 255)->nullable()->after('home_organization_name');

            $table->index('employment_type');
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropIndex(['employment_type']);
            $table->dropColumn([
                'employment_type',
                'home_organization_id',
                'home_organization_name',
                'named_position',
            ]);
        });
    }
};
