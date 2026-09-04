<?php

namespace App\Models;

use App\Core\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    protected $fillable = [
        'tenant_id', 
        'user_id', 
        'action', 
        'entity_type', 
        'entity_id', 
        'details', 
        'ip_address'
    ];
}
