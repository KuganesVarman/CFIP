<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_module_results', function (Blueprint $table) {
            // /learners/results batch endpoint returns course-level rows with no moduleId
            $table->string('module_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('learner_module_results', function (Blueprint $table) {
            $table->string('module_id')->nullable(false)->change();
        });
    }
};
