<?php

namespace App\Models;

use App\Core\Model;

class Project extends Model
{
    protected $table = 'projects';
    protected $fillable = [
        'tenant_id', 
        'code', 
        'name', 
        'description', 
        'type', 
        'status', 
        'priority', 
        'start_date', 
        'end_date', 
        'budget', 
        'currency', 
        'progress', 
        'created_by'
    ];
}
