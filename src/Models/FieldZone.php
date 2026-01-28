<?php

namespace CoreFly\Models;

class FieldZone extends BaseModel
{
    protected string $table = 'field_zones';
    
    protected array $fillable = [
        'tenant_id',
        'name',
        'manager',
        'target',
        'status'
    ];
}
