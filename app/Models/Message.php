<?php

namespace App\Models;

use App\Core\Model;

class Message extends Model
{
    protected $table = 'messages';
    protected $fillable = [
        'tenant_id', 
        'conversation_id', 
        'sender_id', 
        'content', 
        'type', 
        'attachments', 
        'is_read'
    ];
}
