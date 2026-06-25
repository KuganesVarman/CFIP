<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_module_results', function (Blueprint $table) {
            // These fields can legitimately be null/empty from the iSpring API
            $table->string('module_title')->nullable()->change();
            $table->string('enrollment_id')->nullable()->change();
            $table->string('completion_status')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('learner_module_results', function (Blueprint $table) {
            $table->string('module_title')->nullable(false)->change();
            $table->string('enrollment_id')->nullable(false)->change();
            $table->string('completion_status')->nullable(false)->change();
        });
    }
};
