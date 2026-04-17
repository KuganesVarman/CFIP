<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('learner_module_results', function (Blueprint $table) {
        $table->id();
        $table->string('user_id');
        $table->string('course_id');
        $table->string('module_id');
        $table->string('module_title');
        $table->string('course_item_id');
        $table->string('enrollment_id');
        $table->dateTime('access_date')->nullable();
        $table->dateTime('completion_date')->nullable();
        $table->integer('time_spent')->nullable();
        $table->string('completion_status');
        $table->integer('progress');
        $table->boolean('is_overdue');
        $table->integer('views_count');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learner_module_results');
    }
};
