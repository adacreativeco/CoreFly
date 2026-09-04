<?php

namespace App\Models;

use App\Core\Model;

class HelpdeskMessage extends Model
{
    protected $table = 'helpdesk_messages';
    protected $fillable = [
        'id',
        'tenant_id',
        'ticket_id',
        'user_id',
        'user_name',
        'message',
        'created_at'
    ];
}
