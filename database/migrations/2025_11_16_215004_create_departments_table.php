<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('department_id')->unique();        // departmentId
            $table->string('name');                           // name
            $table->string('parent_department_id')->nullable(); // parentDepartmentId
            $table->string('code')->nullable();               // code
            $table->string('subordination_type')->nullable(); // subordination.subordinationType
            $table->string('co_subordination_type')->nullable(); // coSubordination.subordinationType
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
