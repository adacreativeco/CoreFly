<?php

namespace App\Models;

use App\Core\Model;

class AccountingInvoice extends Model
{
    protected $table = 'accounting_invoices';
    protected $fillable = [
        'id',
        'tenant_id',
        'account_id',
        'number',
        'type',
        'title',
        'customer_name',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_total',
        'total',
        'currency',
        'status',
        'notes',
        'created_by'
    ];
}
