<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_module_results', function (Blueprint $table) {
            // Drop the 2-part unique key (course_item_id, user_id)
            $table->dropUnique('lmr_course_item_user_unique');

            // Add 3-part unique key: one row per user per trial
            // This lets us store all 3 quiz/assessment trials and apply
            // "pass at least once" logic in the analytics controller.
            $table->unique(
                ['course_item_id', 'user_id', 'enrollment_id'],
                'lmr_trial_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('learner_module_results', function (Blueprint $table) {
            $table->dropUnique('lmr_trial_unique');
            $table->unique(['course_item_id', 'user_id'], 'lmr_course_item_user_unique');
        });
    }
};
