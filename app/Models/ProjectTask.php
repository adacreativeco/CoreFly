<?php

namespace App\Models;

use App\Core\Model;

class ProjectTask extends Model
{
    protected $table = 'project_tasks';
    protected $fillable = [
        'tenant_id', 
        'project_id', 
        'parent_id', 
        'assignee_id', 
        'created_by', 
        'title', 
        'description', 
        'type', 
        'status', 
        'priority', 
        'due_date'
    ];
}
