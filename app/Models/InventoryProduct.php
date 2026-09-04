<?php

namespace App\Models;

use App\Core\Model;

class InventoryProduct extends Model
{
    protected $table = 'inventory_products';
    protected $fillable = [
        'id',
        'tenant_id',
        'category_id',
        'name',
        'sku',
        'barcode',
        'description',
        'quantity',
        'min_quantity',
        'unit',
        'unit_cost',
        'unit_price',
        'location',
        'status',
        'created_by'
    ];
}
