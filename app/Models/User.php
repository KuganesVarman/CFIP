<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    // Primary key is 'id' (default)
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'ispring_user_id',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
        'must_change_password' => 'boolean',
    ];
}
