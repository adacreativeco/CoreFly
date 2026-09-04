<?php

namespace App\Models;

use App\Core\Model;

class ConversationParticipant extends Model
{
    protected $table = 'conversation_participants';
    protected $fillable = [
        'conversation_id', 
        'user_id', 
        'role', 
        'joined_at', 
        'last_read_at'
    ];
}
