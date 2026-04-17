<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IspringUser extends Model
{
    protected $table = 'users_ispring';

    protected $fillable = [
        'user_id',
        'role',
        'role_id',
        'department_id',
        'status',
        'fields',
        'user_roles',
        'groups',
        'added_date',
        'last_login_date',
        'subordination_type',
        'co_subordination_type',
    ];

    protected $casts = [
        'fields'       => 'array',
        'user_roles'   => 'array',
        'groups'       => 'array',
        'added_date'   => 'date',
        'last_login_date' => 'date',
    ];
}
