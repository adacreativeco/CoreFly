<?php

namespace App\Models;

use App\Core\Model;

class ProjectMember extends Model
{
    protected $table = 'project_members';
    protected $fillable = [
        'project_id', 
        'user_id', 
        'role', 
        'responsibilities',
        'joined_at'
    ];
}
