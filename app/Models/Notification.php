<?php

namespace App\Models;

use App\Core\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $fillable = [
        'tenant_id', 
        'user_id', 
        'type', 
        'title', 
        'content', 
        'action_url', 
        'is_read', 
        'read_at'
    ];
}
