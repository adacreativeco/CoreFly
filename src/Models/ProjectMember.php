<?php

declare(strict_types=1);

namespace CoreFly\Models;

class ProjectMember extends BaseModel
{
    protected string $table = 'project_members';
    protected string $primaryKey = 'id';

    public string $id;
    public string $project_id;
    public string $user_id;
    public string $role = 'member';
    public ?string $created_at = null;

    protected array $fillable = [
        'id', 'project_id', 'user_id', 'role'
    ];

    protected array $casts = [
        'created_at' => 'datetime'
    ];
}
