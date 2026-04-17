<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $fillable = [
        'content_item_id', 'title', 'subtitle', 'description', 'user_id', 'added_date', 'view_url',
        'type', 'content_item_type', 'course_fields'
    ];
    protected $casts = [
        'course_fields' => 'array',
        'added_date' => 'datetime',
    ];
}

