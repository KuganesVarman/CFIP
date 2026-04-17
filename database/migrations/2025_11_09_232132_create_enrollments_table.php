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
    Schema::create('enrollments', function (Blueprint $table) {
        $table->id();
        $table->string('enrollment_id')->unique();
        $table->string('course_id');
        $table->string('learner_id');
        $table->date('access_date')->nullable();
        $table->integer('enrollment_type_group')->nullable();
        $table->boolean('should_lock_after_due_date')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
