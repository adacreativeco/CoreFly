<?php

namespace CoreFly\Models;

class FieldTeam extends BaseModel
{
    protected string $table = 'field_teams';
    
    protected array $fillable = [
        'tenant_id',
        'name',
        'description',
        'member_count',
        'active_task_count'
    ];
}
