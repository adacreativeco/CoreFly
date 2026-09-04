<?php

namespace App\Models;

use App\Core\Model;

class CallSignal extends Model
{
    protected $table = 'call_signals';
    protected $fillable = [
        'id',
        'tenant_id',
        'call_id',
        'sender_id',
        'type',
        'payload',
        'is_processed',
        'created_at'
    ];
}
