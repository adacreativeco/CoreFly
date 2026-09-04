<?php

namespace App\Models;

use App\Core\Model;

class InventoryCategory extends Model
{
    protected $table = 'inventory_categories';
    protected $fillable = [
        'id',
        'tenant_id',
        'name',
        'description',
        'created_at'
    ];
}
