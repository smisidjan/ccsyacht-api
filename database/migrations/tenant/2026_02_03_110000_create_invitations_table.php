<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('token')->unique();
            $table->string('role')->default('user');
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            $table->foreignUuid('invited_by')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('accepted_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'status']);
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
