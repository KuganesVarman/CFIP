<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = [
        'enrollment_id', 'course_id', 'learner_id', 'access_date', 'enrollment_type_group', 'should_lock_after_due_date'
    ];
}

