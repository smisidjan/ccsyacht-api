<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection($this->connection)->create('guest_role_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('organization_id');
            $table->string('allowed_role', 50);
            $table->timestamps();

            $table->foreign('organization_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            // One entry per role per organization
            $table->unique(['organization_id', 'allowed_role']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('guest_role_permissions');
    }
};
