<?php

namespace App\Models;

use App\Core\Model;

class HelpdeskTicket extends Model
{
    protected $table = 'helpdesk_tickets';
    protected $fillable = [
        'id',
        'tenant_id',
        'title',
        'description',
        'priority',
        'status',
        'category',
        'assigned_to',
        'created_by'
    ];
}
