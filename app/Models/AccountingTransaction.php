<?php

namespace App\Models;

use App\Core\Model;

class AccountingTransaction extends Model
{
    protected $table = 'accounting_transactions';
    protected $fillable = [
        'id',
        'tenant_id',
        'account_id',
        'invoice_id',
        'type',
        'amount',
        'currency',
        'date',
        'category',
        'description',
        'payment_method',
        'created_by'
    ];
}
