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
    Schema::create('contents', function (Blueprint $table) {
        $table->id();
        $table->string('content_item_id')->unique();
        $table->string('title');
        $table->string('subtitle')->nullable();
        $table->text('description')->nullable();
        $table->string('user_id')->nullable();
        $table->timestamp('added_date')->nullable();
        $table->string('view_url')->nullable();
        $table->string('type')->nullable();
        $table->string('content_item_type')->nullable();
        $table->json('course_fields')->nullable(); // For array fields
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
