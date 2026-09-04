<?php

namespace App\Models;

use App\Core\Model;

class AccountingInvoiceItem extends Model
{
    protected $table = 'accounting_invoice_items';
    protected $fillable = [
        'id',
        'tenant_id',
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'total',
        'created_at'
    ];
}
