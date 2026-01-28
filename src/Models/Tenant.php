<?php

declare(strict_types=1);

namespace CoreFly\Models;

class Tenant extends BaseModel
{
    protected string $table = 'tenants';

    public string $id;
    public string $name;
    public string $domain;
    public string $status;
    public array $settings = [];
    public array $active_modules = [];
    
    protected array $fillable = [
        'id', 'name', 'domain', 'status', 'settings', 'active_modules'
    ];
    
    protected array $casts = [
        'settings' => 'array',
        'active_modules' => 'array'
    ];
    
    public function __construct(?array $attributes = null)
    {
        parent::__construct($attributes);
        $this->tenantId = null; // Tenants are global
        $this->settings = []; // Initialize as empty array
        $this->active_modules = [];
    }
}
