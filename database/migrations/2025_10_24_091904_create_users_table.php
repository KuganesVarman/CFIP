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
         Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('user_id')->unique(); // e.g. A22EC0176
        $table->string('password');
        $table->enum('role', ['A', 'PC', 'L']); // Admin, Program Coordinator, Learner
        $table->rememberToken();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
