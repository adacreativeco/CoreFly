<?php

namespace App\Models;

use App\Core\Model;

class FieldTask extends Model
{
    protected $table = 'field_tasks';
    protected $fillable = [
        'id',
        'tenant_id',
        'title',
        'description',
        'team_name',
        'location',
        'status',
        'priority',
        'assigned_to',
        'task_date',
        'created_by'
    ];
}
