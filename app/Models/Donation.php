<?php

namespace App\Models;

use App\Core\Model;

class Donation extends Model
{
    protected $table = 'donations';
    protected $fillable = [
        'id',
        'tenant_id',
        'donor_name',
        'donor_email',
        'donor_phone',
        'amount',
        'currency',
        'campaign_name',
        'payment_method',
        'notes',
        'created_by'
    ];
}
