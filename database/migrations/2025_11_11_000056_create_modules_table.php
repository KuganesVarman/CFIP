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
    Schema::create('modules', function (Blueprint $table) {
        $table->id();
        $table->string('module_id')->unique();
        $table->string('content_item_id');
        $table->string('course_id');
        $table->string('title');
        $table->text('description')->nullable();
        $table->string('author_id')->nullable();
        $table->timestamp('added_date')->nullable();
        $table->json('view_url')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
