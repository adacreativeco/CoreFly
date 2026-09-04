<?php

namespace App\Models;

use App\Core\Model;

class Conversation extends Model
{
    protected $table = 'conversations';
    protected $fillable = [
        'tenant_id', 
        'title', 
        'type', 
        'description', 
        'created_by', 
        'last_message_at'
    ];
}
