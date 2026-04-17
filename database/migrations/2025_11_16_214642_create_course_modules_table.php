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
        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->string('module_id')->unique();  // moduleId
            $table->string('item_id');              // itemId
            $table->string('course_id');            // courseId
            $table->string('title');                // title
            $table->text('description')->nullable(); // description
            $table->string('author_id')->nullable(); // authorId
            $table->timestamp('added_date')->nullable(); // addedDate
            $table->string('type')->nullable();     // type (material, etc.)
            $table->string('view_url')->nullable(); // viewUrl
            $table->unsignedInteger('sequential_number')->nullable(); // sequentialNumber
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_modules');
    }
};
