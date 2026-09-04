<?php

namespace App\Models;

use App\Core\Model;

class CrmActivity extends Model
{
    protected $table = 'crm_activities';
    protected $fillable = [
        'id',
        'tenant_id',
        'related_to_type',
        'related_to_id',
        'type',
        'subject',
        'description',
        'date',
        'status',
        'created_by'
    ];
}
