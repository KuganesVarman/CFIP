<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->string('course_id');
            $table->string('course_code');
            $table->timestamps();

            $table->unique(['domain_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_courses');
    }
};
