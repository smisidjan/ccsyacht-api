<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['uploaded_by']);

            // Make the column nullable
            $table->uuid('uploaded_by')->nullable()->change();

            // Re-add the foreign key with nullOnDelete
            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Add uploaded_by_name for system admin uploads
            $table->string('uploaded_by_name')->nullable()->after('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('uploaded_by_name');

            $table->dropForeign(['uploaded_by']);

            $table->uuid('uploaded_by')->nullable(false)->change();

            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
