<?php

namespace App\Models;

use App\Core\Model;

class PoliticsVolunteer extends Model
{
    protected $table = 'politics_volunteers';
    protected $fillable = [
        'id',
        'tenant_id',
        'full_name',
        'phone',
        'city',
        'district',
        'neighborhood',
        'ballot_box_number',
        'role',
        'notes'
    ];
}
