<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseModule extends Model
{
    protected $table = 'course_modules';

    protected $fillable = [
        'module_id',
        'item_id',
        'course_id',
        'title',
        'description',
        'author_id',
        'added_date',
        'type',
        'view_url',
        'sequential_number',
    ];

    protected $casts = [
        'added_date'        => 'datetime',
        'sequential_number' => 'integer',
    ];
}
