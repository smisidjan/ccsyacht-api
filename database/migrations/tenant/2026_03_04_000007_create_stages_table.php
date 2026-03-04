<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('area_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->boolean('requires_release_form')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['area_id', 'sort_order']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stages');
    }
};
