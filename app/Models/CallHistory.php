<?php

namespace App\Models;

use App\Core\Model;

class CallHistory extends Model
{
    protected $table = 'call_history';
    protected $fillable = [
        'id',
        'tenant_id',
        'caller_id',
        'callee_id',
        'call_type',
        'status',
        'started_at',
        'ended_at',
        'duration_seconds',
        'created_at'
    ];
}
