<?php

namespace App\Models;

use App\Core\Model;

class Department extends Model
{
    protected $table = 'departments';
    protected $fillable = [
        'tenant_id', 
        'name', 
        'code', 
        'description', 
        'parent_id', 
        'manager_id', 
        'location', 
        'is_active'
    ];
}
