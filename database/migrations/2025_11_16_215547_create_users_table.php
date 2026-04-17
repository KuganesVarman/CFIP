<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users_ispring', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->unique();         // userId
            $table->string('role')->nullable();          // role
            $table->string('role_id')->nullable();       // roleId
            $table->string('department_id')->nullable(); // departmentId
            $table->integer('status')->nullable();       // status

            $table->json('fields')->nullable();          // fields[]
            $table->json('user_roles')->nullable();      // userRoles[]
            $table->json('groups')->nullable();          // groups[]

            $table->date('added_date')->nullable();
            $table->date('last_login_date')->nullable();

            $table->string('subordination_type')->nullable();
            $table->string('co_subordination_type')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_ispring');
    }
};
