<?php

namespace App\Models;

use App\Core\Model;

class AccountingAccount extends Model
{
    protected $table = 'accounting_accounts';
    protected $fillable = [
        'id',
        'tenant_id',
        'code',
        'name',
        'type',
        'balance',
        'currency',
        'bank_name',
        'iban',
        'tax_number',
        'tax_office',
        'notes',
        'created_at'
    ];
}
