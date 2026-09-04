<?php

namespace App\Models;

use App\Core\Model;

class InventoryMovement extends Model
{
    protected $table = 'inventory_movements';
    protected $fillable = [
        'id',
        'tenant_id',
        'product_id',
        'movement_type',
        'quantity',
        'previous_quantity',
        'new_quantity',
        'reference_number',
        'reason',
        'created_by'
    ];
}
