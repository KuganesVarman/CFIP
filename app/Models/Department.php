<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'department_id',
        'name',
        'parent_department_id',
        'code',
        'subordination_type',
        'co_subordination_type',
    ];
}
