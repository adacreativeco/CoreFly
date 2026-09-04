<?php

namespace App\Models;

use App\Core\Model;

class CalendarEvent extends Model
{
    protected $table = 'calendar_events';
    protected $fillable = [
        'id',
        'tenant_id',
        'title',
        'description',
        'event_type',
        'start_date',
        'end_date',
        'location',
        'created_by'
    ];
}
