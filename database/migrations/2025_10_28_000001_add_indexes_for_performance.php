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
        // Add indexes for better query performance
        Schema::table('courses', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('category');
            $table->index('created_at');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->index(['course_id', 'order']);
        });

        Schema::table('contents', function (Blueprint $table) {
            $table->index(['module_id', 'parent_id', 'order']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['category']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropIndex(['course_id', 'order']);
        });

        Schema::table('contents', function (Blueprint $table) {
            $table->dropIndex(['module_id', 'parent_id', 'order']);
            $table->dropIndex(['type']);
        });
    }
};
