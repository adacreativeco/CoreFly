<?php

declare(strict_types=1);

namespace CoreFly\Models;

class Department extends BaseModel
{
    protected string $table = 'departments';
    
    public string $id;
    public string $tenant_id;
    public string $name;
    public ?string $description = null;
    public ?string $manager_id = null;
    public ?string $parent_id = null;
    public string $status = 'active';
    public ?string $created_at = null;
    public ?string $updated_at = null;

    protected array $fillable = [
        'id', 'tenant_id', 'name', 'description', 'manager_id', 'parent_id', 'status'
    ];

    public function getManager(): ?User
    {
        return $this->manager_id ? User::find($this->manager_id) : null;
    }

    public function getUsers(): array
    {
        return User::where('department_id', $this->id)->get();
    }
}
