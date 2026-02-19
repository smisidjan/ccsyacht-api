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
        Schema::connection($this->connection)->create('tenant_users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('tenant_id');
            $table->uuid('user_id');
            $table->timestamps();

            $table->unique(['email', 'tenant_id']);
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('tenant_users');
    }
};
