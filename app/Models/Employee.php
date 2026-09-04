<?php

namespace App\Models;

use App\Core\Model;

class Employee extends Model
{
    protected $table = 'employees';
    protected $fillable = [
        'tenant_id', 
        'user_id', 
        'employee_number', 
        'hire_date',
        'termination_date', 
        'department_id', 
        'position_id',
        'manager_id', 
        'employment_type', 
        'salary', 
        'currency', 
        'status'
    ];
}
