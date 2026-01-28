<?php

declare(strict_types=1);

namespace CoreFly\Models;

class Event extends BaseModel
{
    protected string $table = 'events';
    protected string $primaryKey = 'id';

    // Minimal properties to satisfy references
    public string $id;
    public string $tenant_id;
    public ?string $department_id = null;
    public string $user_id;
    public string $title;
    public ?string $description = null;
    public string $start_date;
    public ?string $end_date = null;
    public ?string $location = null;
    public ?string $type = null;
    public string $color = '#3b82f6';
    public bool $all_day = false;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    protected array $fillable = [
        'id', 'tenant_id', 'department_id', 'user_id', 'title', 'description', 'start_date',
        'end_date', 'location', 'type', 'color', 'all_day'
    ];

    protected array $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'all_day' => 'boolean'
    ];
}

