<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_module_results', function (Blueprint $table) {
            // Drop the wrong single-column unique index
            $table->dropUnique(['course_item_id']);

            // Add the correct composite unique index:
            // one row per user per course module
            $table->unique(['course_item_id', 'user_id'], 'lmr_course_item_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('learner_module_results', function (Blueprint $table) {
            $table->dropUnique('lmr_course_item_user_unique');
            $table->unique('course_item_id');
        });
    }
};
