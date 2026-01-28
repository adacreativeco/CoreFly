<?php

declare(strict_types=1);

namespace CoreFly\Models;

class Subtask extends BaseModel
{
    protected string $table = 'subtasks';
    
    public string $id;
    public string $tenant_id;
    public string $task_id;
    public string $title;
    public int $is_completed = 0;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    protected array $fillable = [
        'id', 'tenant_id', 'task_id', 'title', 'is_completed'
    ];

    protected array $casts = [
        'is_completed' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
