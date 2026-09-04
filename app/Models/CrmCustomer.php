<?php

namespace App\Models;

use App\Core\Model;

class CrmCustomer extends Model
{
    protected $table = 'crm_customers';
    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'email',
        'phone',
        'company',
        'address',
        'city',
        'country',
        'tax_id',
        'industry',
        'website',
        'lead_source',
        'status',
        'notes',
        'created_by',
        'updated_by'
    ];
}
