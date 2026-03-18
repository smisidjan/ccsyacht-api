<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shipyard_id')->nullable()->constrained('shipyards')->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('project_type', ['new_built', 'refit']);
            $table->enum('status', ['setup', 'active', 'completed', 'archived'])->default('setup');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('general_arrangement_path')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('name');
            $table->index('status');
            $table->index('project_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
