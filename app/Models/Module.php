<?php

namespace App\Models;

// app/Models/Module.php
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'module_id', 'content_item_id', 'course_id', 'title', 'description',
        'author_id', 'added_date', 'view_url'
    ];

    protected $casts = [
        'added_date' => 'datetime',
        'view_url'   => 'array',   // ✅ let Eloquent JSON-encode/decode for you
    ];
}


