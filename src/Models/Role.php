<?php

declare(strict_types=1);

namespace CoreFly\Models;

use Exception;

class Role extends BaseModel
{
    protected string $table = 'roles';
    protected string $primaryKey = 'id';
    
    // Model properties
    public string $id;
    public string $tenant_id;
    public string $name;
    public ?string $description = null;
    public array $permissions = [];
    public bool $is_system = false;
    public string $status;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    
    protected array $fillable = [
        'id', 'tenant_id', 'name', 'description', 'permissions', 'is_system', 'status'
    ];
    
    protected array $casts = [
        'permissions' => 'array',
        'is_system' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function getUsers(): array
    {
        return User::where('role_id', $this->id);
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        
        if (isset($permissions['*']) && in_array('*', $permissions['*'])) {
            return true;
        }

        [$module, $action] = explode(':', $permission);
        
        return isset($permissions[$module]) && 
               (in_array($action, $permissions[$module]) || in_array('*', $permissions[$module]));
    }

    public function addPermission(string $permission): self
    {
        [$module, $action] = explode(':', $permission);
        
        $permissions = $this->permissions ?? [];
        
        if (!isset($permissions[$module])) {
            $permissions[$module] = [];
        }
        
        if (!in_array($action, $permissions[$module])) {
            $permissions[$module][] = $action;
        }
        
        $this->permissions = $permissions;
        return $this;
    }

    public function removePermission(string $permission): self
    {
        [$module, $action] = explode(':', $permission);
        
        $permissions = $this->permissions ?? [];
        
        if (isset($permissions[$module])) {
            $permissions[$module] = array_filter(
                $permissions[$module], 
                fn($perm) => $perm !== $action
            );
            
            if (empty($permissions[$module])) {
                unset($permissions[$module]);
            }
        }
        
        $this->permissions = $permissions;
        return $this;
    }

    public function isSystemRole(): bool
    {
        return $this->is_system === true;
    }

    public function canBeDeleted(): bool
    {
        return !$this->isSystemRole() && empty($this->getUsers());
    }

    public function getPermissionList(): array
    {
        $permissions = $this->permissions ?? [];
        $permissionList = [];

        foreach ($permissions as $module => $actions) {
            foreach ($actions as $action) {
                $permissionList[] = "{$module}:{$action}";
            }
        }

        return $permissionList;
    }

    public function hasModuleAccess(string $module): bool
    {
        $permissions = $this->permissions ?? [];
        
        return isset($permissions['*']) || 
               isset($permissions[$module]) || 
               (isset($permissions[$module]) && !empty($permissions[$module]));
    }
}
