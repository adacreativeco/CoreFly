<?php

declare(strict_types=1);

namespace CoreFly\Models;

class MessageGroup extends BaseModel
{
    protected string $table = 'message_groups';
    
    public ?string $id = null;
    public ?string $tenant_id = null;
    public ?string $name = null;
    public ?string $type = 'public';
    public ?string $created_by = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    protected array $fillable = [
        'id', 'tenant_id', 'name', 'type', 'created_by'
    ];

    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
