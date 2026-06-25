<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Step 1: Remove duplicates, keeping the highest id per course_item_id ──
        // MySQL won't let you DELETE from a table while SELECTing from it directly,
        // so we wrap the subquery in an extra derived table.
        DB::statement('
            DELETE FROM learner_module_results
            WHERE id NOT IN (
                SELECT id FROM (
                    SELECT MAX(id) as id
                    FROM learner_module_results
                    GROUP BY course_item_id
                ) AS keep_rows
            )
        ');

        $removed = DB::table('learner_module_results')->count();
        \Illuminate\Support\Facades\Log::info("Deduplication done. Rows remaining: {$removed}");

        // ── Step 2: Now safe to add the unique index ───────────────
        Schema::table('learner_module_results', function (Blueprint $table) {
            $table->unique('course_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('learner_module_results', function (Blueprint $table) {
            $table->dropUnique(['course_item_id']);
        });
    }
};
