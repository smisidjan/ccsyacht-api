<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            // Limieten (NULL = unlimited)
            $table->unsignedInteger('max_projects')->default(1);
            $table->unsignedInteger('max_users')->nullable();

            // Status: active, cancelled
            $table->string('status', 20)->default('active');

            // Audit
            $table->timestamps();
            $table->foreignUuid('created_by')->nullable()->constrained('system_admins')->nullOnDelete();

            // Index for lookup
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};
