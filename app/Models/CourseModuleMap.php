<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseModuleMap extends Model
{
    protected $table = 'course_module_map';

    protected $fillable = [
        'course_id',
        'module_id',
        'module_title',
    ];
}
