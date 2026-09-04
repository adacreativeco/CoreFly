<?php

namespace App\Models;

use App\Core\Model;

class Position extends Model
{
    protected $table = 'positions';
    protected $fillable = [
        'tenant_id', 
        'title', 
        'code', 
        'description',
        'department_id', 
        'level', 
        'min_salary', 
        'max_salary',
        'currency', 
        'reporting_to', 
        'is_active'
    ];
}
