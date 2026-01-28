<?php

declare(strict_types=1);

namespace CoreFly\Models;

class Task extends BaseModel
{
    protected string $table = 'tasks';
    protected string $primaryKey = 'id';

    // Minimal properties to satisfy references
    public string $id;
    public string $tenant_id;
    public ?string $department_id = null;
    public string $title;
    public ?string $description = null;
    public ?string $assigned_to = null;
    public string $status = 'todo';
    public ?string $priority = null;
    public ?string $due_date = null;
    public ?int $progress = null;
    public ?string $start_date = null;
    public int $position = 0;
    public ?string $project_id = null;
    public mixed $tags = null;
    public ?float $estimated_hours = null;

    protected array $fillable = [
        'id', 'tenant_id', 'department_id', 'title', 'description', 'assigned_to', 'status',
        'priority', 'due_date', 'progress', 'project_id', 'start_date', 'position'
    ];

    protected array $casts = [
        'progress' => 'integer',
        'position' => 'integer',
        'due_date' => 'datetime',
        'start_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'tags' => 'json',
        'estimated_hours' => 'float'
    ];
}

