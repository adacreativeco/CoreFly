<?php

namespace App\Models;

use App\Core\Model;

class CrmDeal extends Model
{
    protected $table = 'crm_deals';
    protected $fillable = [
        'id',
        'tenant_id',
        'customer_id',
        'title',
        'value',
        'currency',
        'stage',
        'probability',
        'expected_close_date',
        'notes',
        'assigned_to',
        'created_by',
        'updated_by'
    ];
}
