<?php

namespace CoreFly\Models;

class AuditLog extends BaseModel
{
    protected string $table = 'audit_logs';

    // Define properties to ensure they are recognized
    public ?string $id = null;
    public ?string $tenant_id = null;
    public ?string $user_id = null;
    public ?string $action = null;
    public ?string $resource_type = null;
    public ?string $resource_id = null;
    public $old_values = null; // Can be array or string
    public $new_values = null; // Can be array or string
    public ?string $ip_address = null;
    public ?string $user_agent = null;

    protected array $casts = [
        'old_values' => 'json',
        'new_values' => 'json'
    ];

    public function toArray(): array
    {
        $data = parent::toArray();
        // Ensure values are decoded if they are strings (handled by casts in BaseModel, but let's double check)
        // BaseModel castAttribute handles json decoding for properties in $casts.
        // But toArray calls get_object_vars.
        // If properties are public and set, they are returned.
        
        // Let's rely on BaseModel's toArray which handles casts.
        // But the parent implementation of toArray checks casts.
        
        return $data;
    }
}
