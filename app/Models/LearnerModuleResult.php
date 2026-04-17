<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearnerModuleResult extends Model
{
    protected $table = 'learner_module_results';

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'course_id',
        'module_id',
        'module_title',
        'course_item_id',
        'enrollment_id',
        'access_date',
        'completion_date',
        'time_spent',
        'completion_status',
        'progress',
        'is_overdue',
        'views_count'
    ];
}
