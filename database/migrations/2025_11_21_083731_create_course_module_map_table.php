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
    Schema::create('course_module_map', function (Blueprint $table) {
        $table->id();
        $table->string('course_id');       // UUID from iSpring
        $table->string('module_id');       // UUID from iSpring
        $table->string('module_title');    // Clean text title
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('course_module_map');
}

};
