<?php

namespace App\Models;

use App\Core\Model;

class Announcement extends Model
{
    protected $table = 'announcements';
    protected $fillable = [
        'id',
        'tenant_id',
        'title',
        'content',
        'priority',
        'is_pinned',
        'created_by',
        'author_name'
    ];
}
